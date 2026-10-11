#!/usr/bin/env bash
# Sync Vite build assets from the git repo into cPanel public_html.
# Usage (on server):
#   bash scripts/sync-public-html.sh
# Or with an explicit path:
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
rsync -a --delete "$ROOT/public/build/" "$PUBLIC_HTML/build/"

# Keep index.php / .htaccess pointing at the Laravel app if this host uses a copy.
if [[ -f "$ROOT/public/index.php" && -f "$PUBLIC_HTML/index.php" ]]; then
  cp -f "$ROOT/public/index.php" "$PUBLIC_HTML/index.php"
  cp -f "$ROOT/public/.htaccess" "$PUBLIC_HTML/.htaccess" 2>/dev/null || true
fi

echo "Synced build assets -> $PUBLIC_HTML/build"
ls -la "$PUBLIC_HTML/build/assets"
