#!/usr/bin/env bash
# Sync Vite build assets from the git repo into cPanel public_html.
# Also installs a cPanel front controller that boots the repo (never the
# stock public/index.php paths — those break when document root is public_html).
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

# cPanel document root is public_html, not repo/public — write a dedicated front controller.
if [[ -f "$PUBLIC_HTML/index.php" ]]; then
  cp -f "$PUBLIC_HTML/index.php" "$PUBLIC_HTML/index.php.bak-before-sync" 2>/dev/null || true
fi

cat > "$PUBLIC_HTML/index.php" <<EOF
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

\$laravelRoot = '${ROOT}';

if (file_exists(\$maintenance = \$laravelRoot.'/storage/framework/maintenance.php')) {
    require \$maintenance;
}

require \$laravelRoot.'/vendor/autoload.php';

/** @var Application \$app */
\$app = require_once \$laravelRoot.'/bootstrap/app.php';

\$app->usePublicPath(__DIR__);

\$app->handleRequest(Request::capture());
EOF

if [[ -f "$ROOT/public/.htaccess" ]]; then
  cp -f "$ROOT/public/.htaccess" "$PUBLIC_HTML/.htaccess"
fi

echo "Synced build assets -> $PUBLIC_HTML/build"
echo "Front controller -> $PUBLIC_HTML/index.php (laravelRoot=$ROOT)"
ls -la "$PUBLIC_HTML/build/assets"
head -n 20 "$PUBLIC_HTML/index.php"
cat "$PUBLIC_HTML/build/manifest.json"
