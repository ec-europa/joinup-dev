#!/usr/bin/env bash

# Run the following line every time the machine is brought up so that the host's IP is retrieved even if it changes
# after a restart.

# First a cleanup from previous config
echo "**** emptying xdebug config ..."
echo -n "" > /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
echo "zend_extension=xdebug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
echo "xdebug.remote_host=$(ip -4 route show default | awk '/^default/ { print $3 }')" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
echo "xdebug.mode=$XDEBUG_MODE" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
echo "xdebug.idekey=$XDEBUG_KEY" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
echo "xdebug.discover_client_host=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
if ! [ -z "$XDEBUG_LOG" ]; then
  echo "xdebug.log=$XDEBUG_LOG" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
fi;

if [ "$PHP_XDEBUG_ENABLED" -eq "1" ]; then
    echo "**** ENABLING XDEBUG ..."
    docker-php-ext-enable xdebug;
fi;

# Run the default startup file.
/usr/local/bin/apache2-foreground
