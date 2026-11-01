#!/usr/bin/env bash
# Point current at a previously retained release. Does not rebuild and does
# not run migrate:rollback (schema changes stay applied).
#
# public_html continues to point at current/public, so switching current is
# enough for the document root to serve the restored release.
#
# Required environment:
#   APP_ROOT   Absolute layout root (GitHub secret PATH_TO_REPO)
#
# Optional environment:
#   RELEASE_ID Specific releases/<id> to restore. Default: shared/previous_release
#   PHP_BIN    PHP CLI binary (default: autodetect php >= 8.2)
set -euo pipefail

log() { printf '%s %s\n' "$(date -u +'%Y-%m-%dT%H:%M:%SZ')" "$*"; }
die() { log "error: $*"; exit 1; }

need_cmd() {
  command -v "$1" >/dev/null 2>&1 || die "required command not found: $1"
}

need_cmd ln
need_cmd mv
need_cmd readlink
need_cmd mkdir

APP_ROOT="${APP_ROOT:?APP_ROOT is required}"
[[ "$APP_ROOT" == /* ]] || die "APP_ROOT must be an absolute path"

if [[ "${APP_ROOT%/}" == */public_html ]]; then
  die "PATH_TO_REPO is ${APP_ROOT}. It must be the parent of public_html, not public_html itself."
fi

RELEASES_DIR="$APP_ROOT/releases"
SHARED_DIR="$APP_ROOT/shared"
CURRENT_LINK="$APP_ROOT/current"
PUBLIC_HTML="$APP_ROOT/public_html"
LOCK_FILE="$APP_ROOT/.deploy.lock"
PREVIOUS_FILE="$SHARED_DIR/previous_release"
CURRENT_FILE="$SHARED_DIR/current_release"

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

  die "PHP 8.2+ CLI not found. Set PHP_BIN to the SiteGround PHP 8.2 binary."
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

read_current_release() {
  if [[ -L "$CURRENT_LINK" ]]; then
    basename "$(readlink -f "$CURRENT_LINK" 2>/dev/null || readlink "$CURRENT_LINK")"
  elif [[ -f "$CURRENT_FILE" ]]; then
    cat "$CURRENT_FILE"
  else
    printf '\n'
  fi
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

ensure_web_root() {
  local path="$1"
  local desired="current/public"

  [[ -d "$APP_ROOT/current/public" || -L "$APP_ROOT/current/public" ]] || die "current/public is missing"

  if [[ -L "$path" || ! -e "$path" ]]; then
    ln -sfn "$desired" "$path"
    return
  fi

  if [[ -d "$path" ]] && public_html_is_link_farm "$path"; then
    refresh_public_html_link_farm
    return
  fi

  if [[ -d "$path" ]] && dir_is_empty "$path"; then
    rmdir "$path"
    ln -sfn "$desired" "$path"
  fi
}

ensure_public_html() {
  ensure_web_root "$PUBLIC_HTML"
}

run_artisan() {
  local release="$1"
  shift
  (cd "$release" && "$PHP_BIN" artisan "$@")
}

restore_inplace_web_roots() {
  local path bak

  for path in "$PUBLIC_HTML" "$APP_ROOT/public"; do
    case "$path" in
      "$PUBLIC_HTML") bak="$APP_ROOT/public_html.inplace.bak" ;;
      *) bak="$APP_ROOT/public.inplace.bak" ;;
    esac
    if [[ -d "$bak" ]]; then
      log "restoring $path from $bak"
      rm -f "$path"
      mv "$bak" "$path"
    fi
  done
  rm -f "$CURRENT_LINK"
}

PHP_BIN="$(detect_php)"
log "using PHP: $PHP_BIN"

if command -v flock >/dev/null 2>&1; then
  exec 9>"$LOCK_FILE"
  flock -n 9 || die "another deployment holds $LOCK_FILE"
fi

[[ -d "$RELEASES_DIR" ]] || die "releases directory missing; has a release-based deploy run yet?"
[[ -L "$CURRENT_LINK" ]] || die "current symlink missing; has a release-based deploy run yet?"

active="$(read_current_release)"
target="${RELEASE_ID:-}"
if [[ -z "$target" && -f "$PREVIOUS_FILE" ]]; then
  target="$(cat "$PREVIOUS_FILE")"
fi
target="$(printf '%s' "$target" | tr -d '[:space:]')"

if [[ -z "$target" ]]; then
  restore_inplace_web_roots
  log "restored in-place document root (no previous release-based deploy)"
  exit 0
fi

[[ "$target" =~ ^[A-Za-z0-9._-]+$ ]] || die "invalid RELEASE_ID: $target"
[[ -d "$RELEASES_DIR/$target" ]] || die "release not found: $RELEASES_DIR/$target"
[[ "$target" != "$active" ]] || die "release $target is already current"

log "rolling back $active -> $target"
swap_symlink "releases/$target" "$CURRENT_LINK"
ensure_public_html

# The release we left becomes the new immediate rollback target.
if [[ -n "$active" ]]; then
  printf '%s\n' "$active" > "$PREVIOUS_FILE"
fi
printf '%s\n' "$target" > "$CURRENT_FILE"

run_artisan "$RELEASES_DIR/$target" optimize:clear || true
run_artisan "$RELEASES_DIR/$target" optimize
log "rolled back to $target"
