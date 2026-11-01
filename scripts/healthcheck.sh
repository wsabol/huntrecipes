#!/usr/bin/env bash
# Production HTTP smoke test. Usage: healthcheck.sh <https-origin>
set -euo pipefail

origin="${1:?usage: healthcheck.sh <https-origin>}"
origin="${origin%/}"

if [[ ! "$origin" =~ ^https:// ]]; then
  echo "error: production origin must be an HTTPS URL, got: $origin" >&2
  exit 1
fi

unclean_path() {
  local url="$1"
  [[ "$url" == *"/public/"* || "$url" == *"/current/"* || "$url" == *"/releases/"* ]]
}

curl_probe() {
  local url="$1"
  local body="$2"
  local meta code effective
  meta="$(mktemp)"

  if ! curl -sS -L --retry 2 --retry-delay 2 --retry-all-errors \
    --max-redirs 5 --max-time 25 \
    -o "$body" -w '%{http_code} %{url_effective}' "$url" > "$meta"
  then
    echo "error: request failed: $url" >&2
    rm -f "$meta"
    return 1
  fi

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
}

assert_not_exposed() {
  local url="$1"
  local body probe code effective path
  body="$(mktemp)"
  echo "GET $url (must not be served)"
  probe="$(curl -sS -L --max-redirs 5 --max-time 15 \
    -o "$body" -w '%{http_code} %{url_effective}' "$url" || true)"
  code="${probe%% *}"
  effective="${probe#* }"
  path="$(printf '%s' "$effective" | sed -E 's#^[a-z]+://[^/]+##')"

  if [[ "$code" != "200" ]]; then
    rm -f "$body"
    return 0
  fi
  if [[ "$url" == *"/public/"* && "$path" != /public/* ]]; then
    rm -f "$body"
    return 0
  fi

  echo "error: $url is web-accessible (HTTP $code, final URL $effective)" >&2
  rm -f "$body"
  return 1
}

run_checks() {
  assert_ok_clean "$origin/up" "$status_body" || return 1
  assert_ok_clean "$origin/api/status" "$status_body" || return 1
  if ! grep -Eq '"is_db_connected"[[:space:]]*:[[:space:]]*true' "$status_body"; then
    echo "error: /api/status did not report is_db_connected=true" >&2
    return 1
  fi

  assert_ok_clean "$origin/sign-in" "$page_body" || return 1
  if ! grep -q 'Continue with Google' "$page_body" || ! grep -q 'Email me a sign-in link' "$page_body"; then
    echo "error: /sign-in did not contain the passwordless sign-in controls" >&2
    return 1
  fi
  if ! grep -Eq 'name="firebase-project-id" content="[^"]+"' "$page_body"; then
    echo "error: /sign-in did not contain Firebase client configuration" >&2
    return 1
  fi

  local app_js app_css
  app_js="$(grep -o '/build/[A-Za-z0-9._/-]*\.js' "$page_body" | head -n 1)"
  app_css="$(grep -o '/build/[A-Za-z0-9._/-]*\.css' "$page_body" | head -n 1)"
  if [[ -z "$app_js" || -z "$app_css" ]]; then
    echo "error: /sign-in did not reference built JS and CSS assets" >&2
    return 1
  fi

  assert_ok_clean "$origin$app_js" "$asset_body" || return 1
  assert_ok_clean "$origin$app_css" "$asset_body" || return 1
  assert_ok_clean "$origin/assets/favicon/favicon.ico" "$asset_body" || return 1

  assert_not_exposed "$origin/shared/.env" || return 1
  assert_not_exposed "$origin/releases" || return 1
  assert_not_exposed "$origin/current" || return 1
  assert_not_exposed "$origin/vendor/autoload.php" || return 1
  assert_not_exposed "$origin/.env" || return 1
  assert_not_exposed "$origin/firebase-sdk.json" || return 1
  assert_not_exposed "$origin/public/sign-in" || return 1
}

status_body="$(mktemp)"
page_body="$(mktemp)"
asset_body="$(mktemp)"
trap 'rm -f "$status_body" "$page_body" "$asset_body"' EXIT

attempts=6
delay=10
for ((attempt = 1; attempt <= attempts; attempt++)); do
  if run_checks; then
    echo "health check passed"
    exit 0
  fi
  if (( attempt < attempts )); then
    echo "health check attempt $attempt/$attempts failed; retrying in ${delay}s..."
    sleep "$delay"
  fi
done

echo "health check failed after ${attempts} attempts" >&2
exit 1
