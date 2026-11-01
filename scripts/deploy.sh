#!/usr/bin/env bash
# Activate a previously uploaded release tarball on the production host.
#
# Layout (APP_ROOT = GitHub secret PATH_TO_REPO):
#
#   $APP_ROOT/
#   ├── current -> releases/<active>
#   ├── releases/<id>/
#   ├── shared/{.env,storage}/
#   └── public_html -> current/public     # production env document root
#
# Required environment:
#   APP_ROOT      Absolute layout root (parent of public_html)
#   RELEASE_ID    e.g. 20260818010203-a1b2c3d
#   TAR_PATH      Absolute path to the uploaded .tar.gz
#
# Optional environment:
#   KEEP_RELEASES Number of release directories to retain (default 5)
#   PHP_BIN       PHP CLI binary (default: autodetect php >= 8.2)
set -euo pipefail

log() { printf '%s %s\n' "$(date -u +'%Y-%m-%dT%H:%M:%SZ')" "$*"; }
die() { log "error: $*"; exit 1; }

need_cmd() {
  command -v "$1" >/dev/null 2>&1 || die "required command not found: $1"
}

need_cmd tar
need_cmd ln
need_cmd mv
need_cmd mkdir
need_cmd readlink

APP_ROOT="${APP_ROOT:?APP_ROOT is required}"
RELEASE_ID="${RELEASE_ID:?RELEASE_ID is required}"
TAR_PATH="${TAR_PATH:?TAR_PATH is required}"
KEEP_RELEASES="${KEEP_RELEASES:-5}"

[[ "$APP_ROOT" == /* ]] || die "APP_ROOT must be an absolute path"
if [[ "${APP_ROOT%/}" == */public_html ]]; then
  die "PATH_TO_REPO is ${APP_ROOT}. It must be the parent of public_html, not public_html itself."
fi
[[ "$RELEASE_ID" =~ ^[A-Za-z0-9._-]+$ ]] || die "invalid RELEASE_ID: $RELEASE_ID"
[[ -f "$TAR_PATH" ]] || die "release archive not found: $TAR_PATH"
[[ "$KEEP_RELEASES" =~ ^[0-9]+$ ]] && (( KEEP_RELEASES >= 2 )) || die "KEEP_RELEASES must be an integer >= 2"

RELEASES_DIR="$APP_ROOT/releases"
SHARED_DIR="$APP_ROOT/shared"
RELEASE_DIR="$RELEASES_DIR/$RELEASE_ID"
CURRENT_LINK="$APP_ROOT/current"
PUBLIC_HTML="$APP_ROOT/public_html"
LOCK_FILE="$APP_ROOT/.deploy.lock"
PREVIOUS_FILE="$SHARED_DIR/previous_release"
CURRENT_FILE="$SHARED_DIR/current_release"
DENY_HTACCESS_MARKER="RECIPES_DENY_WEB_ACCESS"

detect_php() {
  if [[ -n "${PHP_BIN:-}" ]]; then
    printf '%s\n' "$PHP_BIN"
    return
  fi

  local candidate
  for candidate in \
    php \
    php82 \
    php8.2 \
    /usr/local/php82/bin/php \
    /usr/local/bin/php82 \
    /opt/cpanel/ea-php82/root/usr/bin/php
  do
    if command -v "$candidate" >/dev/null 2>&1 || [[ -x "$candidate" ]]; then
      if "$candidate" -r 'exit((int) PHP_VERSION_ID >= 80200 ? 0 : 1);' >/dev/null 2>&1; then
        printf '%s\n' "$candidate"
        return
      fi
    fi
  done

  die "PHP 8.2+ CLI not found. Set PHP_BIN to point to the PHP binary."
}

# Replace the `current` symlink so it points at "$target".
# Relative targets are resolved from the link's directory, not cwd.
swap_symlink() {
  local target="$1"
  local linkpath="$2"
  local tmp="$linkpath.swap.$$"
  local stray="${linkpath%/}/${tmp##*/}"
  local actual prior

  if [[ -d "$linkpath" && ! -L "$linkpath" ]]; then
    die "$linkpath is a directory; move it aside so it can be a symlink to $target"
  fi

  prior="$(readlink "$linkpath" 2>/dev/null || true)"

  rm -f "$tmp"
  ln -sfn "$target" "$tmp" || die "could not stage symlink $tmp -> $target"

  if ! mv -T "$tmp" "$linkpath" 2>/dev/null; then
    # -T unsupported (nothing moved) or the rename failed. Clear both the
    # staging link and anything a following mv dropped inside the old release.
    rm -f "$tmp" "$stray"
    ln -sfn "$target" "$linkpath"
  fi

  actual="$(readlink "$linkpath" 2>/dev/null || true)"
  if [[ "$actual" != "$target" ]]; then
    rm -f "$tmp" "$stray"
    if [[ -n "$prior" ]]; then
      # Do not leave the caller half-flipped: bookkeeping in shared/ has not
      # been written yet, so `current` must go back to what it described.
      ln -sfn "$prior" "$linkpath" 2>/dev/null || true
      log "restored $linkpath -> $prior"
    fi
    die "failed to point $linkpath at $target (got ${actual:-missing})"
  fi

  log "linked $linkpath -> $actual"
}

dir_is_empty() {
  local d="$1"
  local f
  for f in "$d"/* "$d"/.[!.]* "$d"/..?*; do
    if [[ -e "$f" || -L "$f" ]]; then
      return 1
    fi
  done
  return 0
}

install_deny_htaccess() {
  local dir="$1"
  mkdir -p "$dir"
  cat > "$dir/.htaccess" << EOF
# ${DENY_HTACCESS_MARKER}
# Defense in depth if this tree is ever reachable from a mis-set document root.
<IfModule mod_authz_core.c>
  Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
  Order deny,allow
  Deny from all
</IfModule>
EOF
}

seed_dir_from_candidates() {
  local dest="$1"
  shift

  if [[ -d "$dest" ]]; then
    return
  fi

  local src
  for src in "$@"; do
    if [[ -d "$src" && ! -L "$src" ]]; then
      log "seeding $(basename "$dest") from $src"
      mkdir -p "$(dirname "$dest")"
      cp -a "$src" "$dest"
      return
    fi
  done
}

ensure_shared_storage_tree() {
  mkdir -p \
    "$SHARED_DIR/storage/app/public" \
    "$SHARED_DIR/storage/app/private" \
    "$SHARED_DIR/storage/framework/cache/data" \
    "$SHARED_DIR/storage/framework/sessions" \
    "$SHARED_DIR/storage/framework/views" \
    "$SHARED_DIR/storage/framework/twig" \
    "$SHARED_DIR/storage/framework/testing" \
    "$SHARED_DIR/storage/logs"
}

bootstrap_shared() {
  mkdir -p "$RELEASES_DIR" "$SHARED_DIR"
  install_deny_htaccess "$RELEASES_DIR"
  install_deny_htaccess "$SHARED_DIR"

  if [[ ! -f "$SHARED_DIR/.env" ]]; then
    local env_src=""
    local candidate
    for candidate in \
      "$APP_ROOT/.env" \
      "$PUBLIC_HTML/shared/.env" \
      "$PUBLIC_HTML/.env"
    do
      if [[ -f "$candidate" ]]; then
        env_src="$candidate"
        break
      fi
    done
    if [[ -n "$env_src" ]]; then
      log "seeding shared/.env from $env_src (not overwriting later)"
      if [[ -L "$env_src" ]]; then
        cp -a "$(readlink -f "$env_src")" "$SHARED_DIR/.env"
      else
        cp -a "$env_src" "$SHARED_DIR/.env"
      fi
    else
      die "shared/.env is missing. Copy production .env to $SHARED_DIR/.env before deploying."
    fi
  fi
  chmod 600 "$SHARED_DIR/.env" || true

  seed_dir_from_candidates "$SHARED_DIR/storage" \
    "$APP_ROOT/storage" \
    "$PUBLIC_HTML/shared/storage" \
    "$PUBLIC_HTML/storage"
  if [[ ! -d "$SHARED_DIR/storage" ]]; then
    log "creating empty shared/storage tree"
  fi
  ensure_shared_storage_tree

}

link_shared_into_release() {
  rm -f "$RELEASE_DIR/.env"
  ln -s ../../shared/.env "$RELEASE_DIR/.env"

  rm -rf "$RELEASE_DIR/storage"
  ln -s ../../shared/storage "$RELEASE_DIR/storage"

  mkdir -p "$RELEASE_DIR/bootstrap/cache"
  chmod -R u+rwX,g+rwX \
    "$RELEASE_DIR/bootstrap/cache" \
    "$SHARED_DIR/storage" || true

}

run_artisan() {
  local release="$1"
  shift
  # /dev/null stdin: artisan must not consume the rest of a stdin-fed SSH script.
  (cd "$release" && "$PHP_BIN" artisan "$@" </dev/null)
}

validate_production_config() {
  local release="$1"
  (cd "$release" && "$PHP_BIN" -r '
    require "vendor/autoload.php";
    $app = require "bootstrap/app.php";
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $errors = [];
    if (!$app->environment("production")) $errors[] = "APP_ENV must be production";
    if ((bool) config("app.debug")) $errors[] = "APP_DEBUG must be false";
    if (!str_starts_with((string) config("app.url"), "https://")) $errors[] = "APP_URL must use HTTPS";
    if (!(bool) config("session.secure")) $errors[] = "SESSION_SECURE_COOKIE must be true";
    if ((string) config("app.key") === "") $errors[] = "APP_KEY is missing";
    foreach (["project_id", "api_key", "auth_domain"] as $key) {
        if ((string) config("services.firebase.$key") === "") $errors[] = "Firebase $key is missing";
    }
    $credentials = (string) config("services.firebase.credentials");
    $real = realpath($credentials) ?: "";
    if ($credentials === "" || !str_starts_with($credentials, "/") || !is_readable($credentials)) {
        $errors[] = "FIREBASE_CREDENTIALS must be an absolute readable path";
    } elseif (str_starts_with($real, base_path().DIRECTORY_SEPARATOR)) {
        $errors[] = "FIREBASE_CREDENTIALS must be outside the release directory";
    } else {
        $serviceAccount = json_decode((string) file_get_contents($real), true);
        if (!is_array($serviceAccount)
            || ($serviceAccount["project_id"] ?? "") !== (string) config("services.firebase.project_id")
            || ($serviceAccount["client_email"] ?? "") === ""
            || ($serviceAccount["private_key"] ?? "") === "") {
            $errors[] = "FIREBASE_CREDENTIALS is invalid or belongs to another project";
        }
    }
    if ($errors !== []) {
        fwrite(STDERR, implode(PHP_EOL, $errors).PHP_EOL);
        exit(1);
    }
  ')
}

prepare_release() {
  log "extracting $TAR_PATH -> $RELEASE_DIR"
  rm -rf "$RELEASE_DIR"
  mkdir -p "$RELEASE_DIR"
  tar -xzf "$TAR_PATH" -C "$RELEASE_DIR"
  printf '%s\n' "$RELEASE_ID" > "$RELEASE_DIR/.release-id"

  [[ -f "$RELEASE_DIR/public/index.php" ]] || die "release is missing public/index.php"
  [[ -f "$RELEASE_DIR/public/.htaccess" ]] || die "release is missing public/.htaccess"

  link_shared_into_release
  validate_production_config "$RELEASE_DIR"
  # Link directly to shared uploads; SiteGround rejects Laravel's absolute,
  # chained storage symlink when the /home/customer alias differs from Apache's path.
  ln -sfn ../../../shared/storage/app/public "$RELEASE_DIR/public/storage"
  log "laravel prepare: migrate --force, optimize"
  # Schema changes are not undone by application rollback. Keep migrations
  # backward-compatible. migrate:rollback is never run automatically.
  run_artisan "$RELEASE_DIR" migrate --force
  run_artisan "$RELEASE_DIR" optimize
  # QUEUE_CONNECTION=sync and there is no Horizon/Octane/worker — no queue:restart.
}

read_current_release() {
  if [[ -L "$CURRENT_LINK" ]]; then
    basename "$(readlink -f "$CURRENT_LINK" 2>/dev/null || readlink "$CURRENT_LINK")"
  elif [[ -f "$CURRENT_FILE" ]]; then
    cat "$CURRENT_FILE"
  else
    printf '\n'
  fi
}

backup_inplace_tree() {
  local path="$1"
  local bak="$2"

  if [[ -e "$bak" ]]; then
    bak="${bak}.$$"
  fi
  log "moving $path aside to $bak"
  mv "$path" "$bak"
  LAST_INPLACE_BACKUP="$bak"
}

public_html_is_link_farm() {
  local html="$1"
  [[ -d "$html" && ! -L "$html" ]] || return 1
  [[ -L "$html/index.php" ]] || return 1
  local target
  target="$(readlink "$html/index.php")"
  [[ "$target" == *current/public/index.php ]]
}

refresh_public_html_link_farm() {
  local html="$PUBLIC_HTML"
  local src name target

  mkdir -p "$html"

  for src in "$APP_ROOT/current/public"/*; do
    if [[ ! -e "$src" && ! -L "$src" ]]; then
      continue
    fi
    name="${src##*/}"
    ln -sfn "../current/public/$name" "$html/$name"
  done

  if [[ -e "$APP_ROOT/current/public/.htaccess" || -L "$APP_ROOT/current/public/.htaccess" ]]; then
    ln -sfn "../current/public/.htaccess" "$html/.htaccess"
  fi

  for src in "$html"/*; do
    [[ -L "$src" ]] || continue
    target="$(readlink "$src")"
    if [[ "$target" == ../current/public/* ]] && [[ ! -e "$src" ]]; then
      rm -f "$src"
    fi
  done
}

# Point a stable web-root path at current/public without exposing /public in URLs.
# Prefer a directory symlink. If that is not possible
# for public_html, fall back to a directory of relative symlinks into
# current/public so Laravel's own public/.htaccess still handles routing.
ensure_web_root() {
  local path="$1"
  local bak_name="$2"
  local required="$3"
  local desired="current/public"
  local bak="$APP_ROOT/$bak_name"
  LAST_INPLACE_BACKUP=""

  [[ -d "$APP_ROOT/current/public" || -L "$APP_ROOT/current/public" ]] || die "current/public is missing"

  if [[ -L "$path" ]]; then
    ln -sfn "$desired" "$path"
    return
  fi

  if [[ "$path" == "$PUBLIC_HTML" && -d "$path" ]] && public_html_is_link_farm "$path"; then
    log "refreshing public_html symlink farm -> current/public"
    refresh_public_html_link_farm
    return
  fi

  if [[ -d "$path" ]] && dir_is_empty "$path"; then
    rmdir "$path"
  elif [[ -d "$path" ]]; then
    backup_inplace_tree "$path" "$bak"
  elif [[ -e "$path" ]]; then
    die "$path exists and is not a directory or symlink"
  fi

  if ln -s "$desired" "$path"; then
    log "linked $path -> $desired"
    return
  fi

  if [[ "$required" != "required" ]]; then
    log "warning: failed to create $path -> $desired"
    if [[ -n "${LAST_INPLACE_BACKUP:-}" && -d "$LAST_INPLACE_BACKUP" && ! -e "$path" ]]; then
      mv "$LAST_INPLACE_BACKUP" "$path"
    fi
    return
  fi

  log "directory symlink failed; installing public_html file-level symlinks"
  mkdir -p "$path"
  refresh_public_html_link_farm
  [[ -L "$path/index.php" ]] || die "failed to install public_html entrypoint"
}

ensure_public_html() {
  ensure_web_root "$PUBLIC_HTML" "public_html.inplace.bak" "required"
}

activate_release() {
  local previous="$1"

  log "pointing current -> releases/$RELEASE_ID"
  swap_symlink "releases/$RELEASE_ID" "$CURRENT_LINK"
  ensure_public_html
  log "web root: current=$(readlink "$CURRENT_LINK") public_html=$(readlink "$PUBLIC_HTML" 2>/dev/null || echo not-a-symlink)"

  mkdir -p "$SHARED_DIR"
  if [[ -n "$previous" && "$previous" != "$RELEASE_ID" ]]; then
    printf '%s\n' "$previous" > "$PREVIOUS_FILE"
  fi
  printf '%s\n' "$RELEASE_ID" > "$CURRENT_FILE"
}

restore_inplace_web_roots() {
  local path bak

  for path in "$PUBLIC_HTML" "$APP_ROOT/public"; do
    bak=""
    case "$path" in
      "$PUBLIC_HTML") bak="$APP_ROOT/public_html.inplace.bak" ;;
      *) bak="$APP_ROOT/public.inplace.bak" ;;
    esac
    if [[ -d "$bak" ]]; then
      log "first deploy failed; restoring $path from $bak"
      rm -f "$path"
      mv "$bak" "$path"
    fi
  done
  if [[ -L "$CURRENT_LINK" ]]; then
    rm -f "$CURRENT_LINK"
  fi
}

rollback_to() {
  local target="$1"
  [[ -n "$target" ]] || die "no previous release to restore"
  [[ -d "$RELEASES_DIR/$target" ]] || die "previous release directory missing: $target"
  log "rolling current back to releases/$target"
  swap_symlink "releases/$target" "$CURRENT_LINK"
  ensure_public_html
  printf '%s\n' "$target" > "$CURRENT_FILE"
  run_artisan "$RELEASES_DIR/$target" optimize:clear || true
  run_artisan "$RELEASES_DIR/$target" optimize || true
}

cleanup_old_releases() {
  # Do not let a prune failure abort a live release. On bash 4.1–4.3, `set -e`
  # still applies inside functions even when the caller uses `cmd || log`.
  set +e

  local current previous name dir
  current="$(read_current_release)"
  previous=""
  if [[ -f "$PREVIOUS_FILE" ]]; then
    previous="$(cat "$PREVIOUS_FILE")"
  fi

  local protected=1
  if [[ -n "$previous" && "$previous" != "$current" ]]; then
    protected=2
  fi

  local -a extras=()
  for dir in "$RELEASES_DIR"/*; do
    if [[ ! -d "$dir" ]]; then
      continue
    fi
    name="${dir##*/}"
    if [[ "$name" == "$current" || "$name" == "$previous" ]]; then
      continue
    fi
    extras+=("$name")
  done

  local retain_extras=$((KEEP_RELEASES - protected))
  if (( retain_extras < 0 )); then
    retain_extras=0
  fi

  local extra_count="${#extras[@]}"
  local drop=$((extra_count - retain_extras))
  local i
  if (( drop > 0 )); then
    for ((i = 0; i < drop; i++)); do
      log "removing old release ${extras[i]}"
      rm -rf "$RELEASES_DIR/${extras[i]}"
    done
  fi

  set -e
  return 0
}

# --- main ---

PHP_BIN="$(detect_php)"
log "using PHP: $PHP_BIN ($("$PHP_BIN" -r 'echo PHP_VERSION;'))"

mkdir -p "$APP_ROOT"
if command -v flock >/dev/null 2>&1; then
  exec 9>"$LOCK_FILE"
  flock -n 9 || die "another deployment holds $LOCK_FILE"
fi

previous="$(read_current_release)"
log "previous current release: ${previous:-none (first release-based deploy)}"

bootstrap_shared
prepare_release

# Activation happens after extract + shared links + migrate + optimize.
# HTTP health checks run from GitHub Actions against the public origin so a
# production loopback curl cannot false-fail a good release.
activate_release "$previous"

# `artisan about` gathers Composer/storage/cache even with --only=environment and
# can exit 1 after printing the table. `artisan env` only boots the app.
# Keep this in the `if` test, not a helper: on bash 4.1–4.3, `set -e` still
# fires inside functions, so a non-zero artisan would abort without rollback.
log "verifying Laravel boots in $RELEASE_DIR"
if ! (cd "$RELEASE_DIR" && "$PHP_BIN" artisan env </dev/null); then
  log "Laravel failed to boot after activation; restoring previous release"
  if [[ -n "$previous" ]]; then
    rollback_to "$previous"
  else
    restore_inplace_web_roots
  fi
  exit 1
fi

log "removing uploaded tarball"
rm -f "$TAR_PATH" || log "warning: could not remove tarball"

log "pruning old releases"
cleanup_old_releases || log "warning: old-release cleanup failed (release is live)"

log "deployed $RELEASE_ID (public_html -> current/public -> releases/$RELEASE_ID/public)"
log "SiteGround document root must be $PUBLIC_HTML (not $APP_ROOT, not $PUBLIC_HTML/public, not $APP_ROOT/current/public)"
