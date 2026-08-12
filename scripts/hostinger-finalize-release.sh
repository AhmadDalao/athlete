#!/usr/bin/env bash

set -euo pipefail

PHP_BIN="${PHP_BIN:-/opt/alt/php82/usr/bin/php}"
APP_ROOT="${APP_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"

case "$APP_ROOT" in
    *.release-*|*.staging-*|*.tmp-*)
        echo "Refusing to cache Laravel from a temporary release path: $APP_ROOT" >&2
        echo "Swap the release into its final production path, then run this script there." >&2
        exit 1
        ;;
esac

if [[ ! -x "$PHP_BIN" ]]; then
    echo "PHP 8.2 binary is not executable: $PHP_BIN" >&2
    exit 1
fi

if [[ ! -f "$APP_ROOT/artisan" || ! -f "$APP_ROOT/vendor/autoload.php" ]]; then
    echo "APP_ROOT is not a prepared Laravel release: $APP_ROOT" >&2
    exit 1
fi

cd "$APP_ROOT"

"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache

"$PHP_BIN" -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach ((array) config("view.paths") as $path) {
    if (! is_dir($path)) {
        fwrite(STDERR, "Cached view path does not exist: {$path}\n");
        exit(1);
    }
}

echo "Release caches are bound to ".base_path().PHP_EOL;
' 

