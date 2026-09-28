#!/usr/bin/env bash
# HTTP health check run from GitHub Actions against the public origin.
# Usage: healthcheck.sh <origin>
set -euo pipefail

origin="${1:?usage: healthcheck.sh <origin>}"
origin="${origin%/}"

if [[ ! "$origin" =~ ^https?:// ]]; then
  echo "error: origin must be an http(s) URL, got: $origin" >&2
  exit 1
fi

unclean_path() {
  local url="$1"
  [[ "$url" == *"/public/"* || "$url" == *"/current/"* || "$url" == *"/releases/"* ]]
}

curl_probe() {
  local url="$1"
  local body="$2"
  local meta
  meta="$(mktemp)"
  # Follow host-level http→https (or www) redirects, but fail if routing
  # exposes /public, /current, or /releases in the final URL.
  if ! curl -sS -L --retry 2 --retry-delay 2 --retry-all-errors \
    --max-redirs 5 --max-time 25 \
    -o "$body" -w '%{http_code} %{url_effective}' "$url" > "$meta"
  then
    echo "error: request failed: $url" >&2
    cat "$meta" >&2 || true
    rm -f "$meta"
    return 1
  fi
  local code effective
  code="$(cut -d' ' -f1 < "$meta")"
  effective="$(cut -d' ' -f2- < "$meta")"
  rm -f "$meta"
  printf '%s %s\n' "$code" "$effective"
}

assert_ok_clean() {
  local url="$1"
  local body="$2"
  local probe code effective
  echo "GET $url"
  probe="$(curl_probe "$url" "$body")"
  code="${probe%% *}"
  effective="${probe#* }"
  if unclean_path "$effective"; then
    echo "error: $url resolved to unclean URL: $effective" >&2
    return 1
  fi
  if [[ "$code" != "200" ]]; then
    echo "error: $url returned HTTP $code (final URL $effective)" >&2
    return 1
  fi
  return 0
}

mcp_http_registered() {
  local url="$origin/mcp"
  local body probe code
  body="$(mktemp)"
  echo "GET $url"
  probe="$(curl -sS -L --max-redirs 5 --max-time 15 \
    -o "$body" -w '%{http_code} %{url_effective}' "$url" || true)"
  code="${probe%% *}"
  rm -f "$body"
  # Mcp::web registers GET /mcp as 405 Allow: POST. Auth is only on POST.
  case "$code" in
    200|401|403|405|406|415) return 0 ;;
    *) return 1 ;;
  esac
}

assert_not_exposed() {
  local url="$1"
  local body probe code effective path
  body="$(mktemp)"
  echo "GET $url (must not be served)"
  # Follow redirects. Laravel's public/.htaccess 301s /releases/ → /releases
  # (trailing-slash strip) and the app then 404s — that is not exposure.
  probe="$(curl -sS -L --max-redirs 5 --max-time 15 \
    -o "$body" -w '%{http_code} %{url_effective}' "$url" || true)"
  code="${probe%% *}"
  effective="${probe#* }"
  path="$(printf '%s' "$effective" | sed -E 's#^[a-z]+://[^/]+##')"

  if [[ "$code" != "200" ]]; then
    rm -f "$body"
    return 0
  fi

  # /public/login 302 → /login (200) is the intended canonicalization.
  case "$url" in
    */public/*|*/public)
      if [[ "$path" != /public && "$path" != /public/* ]]; then
        rm -f "$body"
        return 0
      fi
      ;;
  esac

  echo "error: $url is web-accessible (HTTP $code, final URL $effective)" >&2
  rm -f "$body"
  return 1
}

run_checks() {
  assert_ok_clean "$origin/api/status" "$status_body" || return 1

  if ! grep -Eq '"is_db_connected"[[:space:]]*:[[:space:]]*true' "$status_body"; then
    echo "error: /api/status did not report is_db_connected=true" >&2
    echo "payload: $(tr '\n' ' ' < "$status_body")" >&2
    return 1
  fi

  assert_ok_clean "$origin/login" "$login_body" || return 1
  if ! grep -Eqi 'login|password|sign in|jrspay' "$login_body"; then
    echo "error: /login did not look like the login page" >&2
    return 1
  fi

  if mcp_http_registered; then
    assert_ok_clean "$origin/.well-known/oauth-protected-resource/mcp" "$oauth_body" || return 1
    if ! grep -Eq '"resource": "https://[^"]+/mcp"' "$oauth_body"; then
      echo "error: protected-resource metadata resource is not the /mcp URL" >&2
      return 1
    fi
    assert_ok_clean "$origin/.well-known/oauth-authorization-server" "$oauth_body" || return 1
    if grep -q '"registration_endpoint"' "$oauth_body"; then
      echo "error: OAuth metadata advertises dynamic client registration" >&2
      return 1
    fi
    if ! grep -q '"mcp:use"' "$oauth_body"; then
      echo "error: production OAuth metadata does not advertise mcp:use" >&2
      return 1
    fi
    if ! grep -q '"client_secret_post"' "$oauth_body"; then
      echo "error: OAuth metadata does not advertise client_secret_post" >&2
      return 1
    fi
  else
    echo "skipping MCP OAuth discovery (GET /mcp is not registered)"
  fi

  # Vite content-hashes the bundle filenames, so discover them from the rendered
  # login page instead of hardcoding them. Empty means the build manifest is missing
  # or the templates failed to resolve it.
  local app_js app_css
  app_js="$(grep -o '/build/[A-Za-z0-9._/-]*\.js' "$login_body" | head -n 1)"
  app_css="$(grep -o '/build/[A-Za-z0-9._/-]*\.css' "$login_body" | head -n 1)"

  if [[ -z "$app_js" || -z "$app_css" ]]; then
    echo "error: /login did not reference a /build/ JS and CSS bundle" >&2
    echo "  js='$app_js' css='$app_css'" >&2
    return 1
  fi

  assert_ok_clean "$origin$app_js" "$asset_body" || return 1
  if ! grep -q . "$asset_body"; then
    echo "error: $app_js was empty" >&2
    return 1
  fi

  assert_ok_clean "$origin$app_css" "$asset_body" || return 1
  assert_ok_clean "$origin/favicon.ico" "$asset_body" || return 1

  assert_not_exposed "$origin/shared/.env" || return 1
  assert_not_exposed "$origin/releases" || return 1
  assert_not_exposed "$origin/current" || return 1
  assert_not_exposed "$origin/vendor/autoload.php" || return 1
  assert_not_exposed "$origin/.env" || return 1
  assert_not_exposed "$origin/public/login" || return 1
}

status_body="$(mktemp)"
login_body="$(mktemp)"
asset_body="$(mktemp)"
oauth_body="$(mktemp)"
trap 'rm -f "$status_body" "$login_body" "$asset_body" "$oauth_body"' EXIT

# First cutover may need a Site Tools document-root change to public_html.
attempts=6
delay=10
i=1
until run_checks; do
  if (( i >= attempts )); then
    echo "health check failed after ${attempts} attempts" >&2
    exit 1
  fi
  echo "health check attempt $i/$attempts failed; retrying in ${delay}s..."
  i=$((i + 1))
  sleep "$delay"
done

echo "health check passed"
