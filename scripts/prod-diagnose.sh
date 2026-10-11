#!/usr/bin/env bash
# Quick production health check. Run on the server:
#   bash scripts/prod-diagnose.sh

set -euo pipefail
cd "$(dirname "$0")/.."

echo "=== git ==="
git rev-parse --short HEAD
git status -sb

echo ""
echo "=== columns ==="
php artisan tinker --execute="echo 'deleted_at='.(Schema::hasColumn('products','deleted_at')?'yes':'no').PHP_EOL; echo 'imei_2='.(Schema::hasColumn('product_imeis','imei_2')?'yes':'no').PHP_EOL; echo 'location_id='.(Schema::hasColumn('product_imeis','location_id')?'yes':'no').PHP_EOL;"

echo ""
echo "=== migrate status (pending) ==="
php artisan migrate:status 2>/dev/null | grep -i "Pending\|No pending" || php artisan migrate:status 2>/dev/null | tail -n 15

echo ""
echo "=== public_html index.php ==="
INDEX="$HOME/public_html/index.php"
if [[ -f "$INDEX" ]]; then
  if grep -q "repositories/Akhitelecom\|usePublicPath" "$INDEX"; then
    echo "front controller: ok (points at app / sets public path)"
  elif grep -q "__DIR__.'/../vendor/autoload.php'" "$INDEX"; then
    echo "front controller: BROKEN (stock Laravel paths — run bash scripts/sync-public-html.sh)"
  else
    echo "front controller: custom (review manually)"
  fi
  grep -E "laravelRoot|vendor/autoload|usePublicPath|__DIR__" "$INDEX" | head -n 8 || true
else
  echo "missing $INDEX"
fi

echo ""
echo "=== hot files ==="
ls -la public/hot 2>/dev/null || echo "repo public/hot: none"
ls -la "$HOME/public_html/hot" 2>/dev/null || echo "public_html/hot: none"

echo ""
echo "=== public_html build ==="
ls -la "$HOME/public_html/build/assets" 2>/dev/null || echo "missing public_html/build/assets"
test -f "$HOME/public_html/build/manifest.json" && echo "manifest: ok" || echo "manifest: MISSING"

echo ""
echo "=== homepage via kernel ==="
php -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(Illuminate\Http\Request::create("/"));
echo "status=".$response->getStatusCode().PHP_EOL;
$body = $response->getContent();
if (preg_match("/ViteException|ErrorException|QueryException|SQLSTATE[^<]+/", $body, $m)) {
    echo "error_hint=".$m[0].PHP_EOL;
}
if ($response->getStatusCode() >= 400) {
    if (preg_match("/<title>([^<]+)<\\/title>/", $body, $t)) echo "title=".$t[1].PHP_EOL;
    echo "snippet=".substr(trim(preg_replace("/\\s+/", " ", strip_tags($body))), 0, 400).PHP_EOL;
} else {
    echo "css_refs=".substr_count($body, "/build/assets/").PHP_EOL;
    if (preg_match_all("/\\/build\\/assets\\/[a-zA-Z0-9_.-]+/", $body, $m)) {
        echo "assets=".implode(",", array_unique($m[0])).PHP_EOL;
    }
}
'

echo ""
echo "=== latest log errors ==="
if [[ -f storage/logs/laravel.log ]]; then
  grep -a "local.ERROR\|production.ERROR\|CRITICAL" storage/logs/laravel.log | tail -n 8 || tail -n 40 storage/logs/laravel.log
else
  echo "no laravel.log"
fi
