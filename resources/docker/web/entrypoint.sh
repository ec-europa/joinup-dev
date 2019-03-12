#!/usr/bin/env bash

# Run the following line every time the machine is brought up so that the host's IP is retrieved even if it changes
# after a restart.
echo "xdebug.remote_host=`ip -4 route show default | awk '/^default/ { print $3 }'`" >> /usr/local/etc/php/conf.d/95-xdebug.ini

# Run the default startup file.
/usr/local/bin/apache2-foreground
