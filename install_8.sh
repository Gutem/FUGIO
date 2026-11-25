#!/bin/bash
# FUGIO PHP 8.x installer (Ubuntu 24.04, PHP 8.3 packages).
# PHP 8.0-8.4 are supported by the code path; the Debian package names may need
# to be adjusted for the distro's default PHP version.
set -e
PHP_VER="${PHP_VER:-8.3}"

apt-get update
apt-get install -y "php${PHP_VER}-dev" "php${PHP_VER}-cli" "php${PHP_VER}-xml" \
  "php${PHP_VER}-mbstring" "php${PHP_VER}-curl" "php${PHP_VER}-bcmath" \
  composer build-essential pkg-config

pip3 install -r requirements.txt

# php-parser 5 (PHP 8 syntax) and AMQP library
(cd Lib/PHP-Parser8 && composer install --no-interaction)
(cd Lib/rabbitmq_php7 && composer install --no-interaction)

# evalhook is required for EVAL_CODE. It has a PHP 8 port in Lib/evalhook8.
(cd Lib/evalhook8 && phpize && ./configure && make && make install)

# uopz must match the running PHP build. Using PECL avoids pinning a PHP 7
# submodule that does not compile against 8.x.
pecl install uopz

for ini in "/etc/php/${PHP_VER}/apache2/php.ini" "/etc/php/${PHP_VER}/cli/php.ini"; do
  [ -f "$ini" ] || continue
  grep -qF 'extension=evalhook.so' "$ini" || echo 'extension=evalhook.so' >> "$ini"
  grep -qF 'extension=uopz.so' "$ini" || echo 'extension=uopz.so' >> "$ini"
  sed -i 's/^disable_functions/; disable_functions/g' "$ini"
  sed -i 's/;phar.readonly = On/phar.readonly = Off/g' "$ini"
done
