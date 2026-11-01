#!/usr/bin/env bash
# Build a production release tarball from the current working tree.
# Expects vendor/ (production Composer install) and public/build/ to already exist.
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
output="${1:?usage: package-release.sh <output-tarball>}"
excludes="$root/scripts/release-excludes.txt"
stage="$(mktemp -d "${TMPDIR:-/tmp}/hunt-recipes-release.XXXXXX")"

cleanup() {
  rm -rf "$stage"
}
trap cleanup EXIT

if [[ ! -d "$root/vendor" ]]; then
  echo "error: vendor/ is missing; run composer install --no-dev first" >&2
  exit 1
fi

if [[ ! -d "$root/public/build" ]]; then
  echo "error: public/build/ is missing; run npm ci && npm run build first" >&2
  exit 1
fi

# The app resolves hashed asset URLs through this at runtime, so a release without it
# renders pages with no JS or CSS.
if [[ ! -f "$root/bootstrap/build-manifest.json" ]]; then
  echo "error: bootstrap/build-manifest.json is missing; run npm run build first" >&2
  exit 1
fi

mkdir -p "$(dirname "$output")"

# GNU tar --exclude-from is available on GitHub-hosted Ubuntu runners; rsync is not
# assumed. --exclude-vcs drops .git even if the exclude list drifts.
tar -C "$root" \
  --exclude-vcs \
  --exclude-from="$excludes" \
  --exclude='./.env' \
  --exclude='./.git' \
  --exclude='./.github' \
  --exclude='./tests' \
  --exclude='./node_modules' \
  --exclude='./storage' \
  --exclude='./scripts' \
  --exclude='./public/assets/images/recipes/*' \
  --exclude='./public/assets/images/users/*' \
  -cf - . | tar -C "$stage" -xf -

mkdir -p "$stage/public/assets/images/recipes" "$stage/public/assets/images/users"
cp "$root/public/assets/images/recipes/generic_recipe.jpg" "$stage/public/assets/images/recipes/"
cp "$root/public/assets/images/users/generic_avatar.jpg" "$stage/public/assets/images/users/"

# Belt-and-suspenders: never ship credentials or VCS even if excludes drift.
rm -rf "$stage/.git" "$stage/.github" "$stage/tests" "$stage/node_modules" "$stage/storage" "$stage/scripts"
rm -f "$stage/.env" "$stage/.env.backup" "$stage/.env.production" "$stage/firebase-sdk.json"

if [[ ! -f "$stage/bootstrap/build-manifest.json" ]]; then
  echo "error: the release excludes dropped bootstrap/build-manifest.json" >&2
  exit 1
fi

tar -czf "$output" -C "$stage" .
echo "Wrote $output ($(du -h "$output" | awk '{print $1}'))"
