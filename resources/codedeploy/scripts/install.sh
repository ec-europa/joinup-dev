#!/bin/bash
cd /var/www/html

while [ ! -f /usr/local/etc/subsite/subsite.ini ]
do
  sleep 2
done

vendor/bin/phing -propertyfile /usr/local/etc/subsite/subsite.ini install setup-acceptance 2>&1 >> /var/log/subsite/install.log
chown -R www-data:www-data /var/www/html/*