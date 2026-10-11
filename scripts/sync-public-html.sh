#!/usr/bin/env bash
# Sync Vite build assets from the git repo into cPanel public_html.
# Usage:
#   bash scripts/sync-public-html.sh
#   PUBLIC_HTML=/home/akhitele/public_html bash scripts/sync-public-html.sh

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PUBLIC_HTML="${PUBLIC_HTML:-$HOME/public_html}"

if [[ ! -d "$PUBLIC_HTML" ]]; then
  echo "public_html not found at: $PUBLIC_HTML"
  echo "Set PUBLIC_HTML=/path/to/public_html and re-run."
  exit 1
fi

mkdir -p "$PUBLIC_HTML/build/assets"

# Prefer rsync when available; fall back to cp (shared hosts often lack rsync).
if command -v rsync >/dev/null 2>&1; then
  rsync -a --delete "$ROOT/public/build/" "$PUBLIC_HTML/build/"
else
  rm -rf "$PUBLIC_HTML/build/assets"
  mkdir -p "$PUBLIC_HTML/build/assets"
  cp -f "$ROOT/public/build/manifest.json" "$PUBLIC_HTML/build/manifest.json"
  cp -f "$ROOT/public/build/assets/"* "$PUBLIC_HTML/build/assets/"
fi

if [[ -f "$ROOT/public/index.php" && -f "$PUBLIC_HTML/index.php" ]]; then
  cp -f "$ROOT/public/index.php" "$PUBLIC_HTML/index.php"
  if [[ -f "$ROOT/public/.htaccess" ]]; then
    cp -f "$ROOT/public/.htaccess" "$PUBLIC_HTML/.htaccess"
  fi
fi

echo "Synced build assets -> $PUBLIC_HTML/build"
ls -la "$PUBLIC_HTML/build/assets"
cat "$PUBLIC_HTML/build/manifest.json"
