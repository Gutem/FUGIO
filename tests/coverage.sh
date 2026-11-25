#!/bin/sh
# Best-effort coverage for the PHP 8 port. Requires Xdebug (or pcov) in the
# test image; the PHP 8 CI image installs Xdebug.
set -eu
HERE=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
cd "$HERE"
[ -d vendor ] || composer install --no-interaction --no-progress
[ -d ../Lib/PHP-Parser8/vendor ] || (cd ../Lib/PHP-Parser8 && composer install --no-interaction --no-progress)
XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-text
