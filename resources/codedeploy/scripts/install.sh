#!/bin/bash
cd /srv/project

while [ ! -f /usr/local/etc/subsite/subsite.ini ]
do
  sleep 2
done

# This can be removed once the ami is rebuild:
nohup echo "shutdown();" | isql-vt &

chown -R www-data:www-data /srv/project
# Background and detach to work around time constrains of AWS CodeDeploy.
nohup sudo -u www-data vendor/bin/phing -propertyfile /usr/local/etc/subsite/subsite.ini setup-acceptance  >> /var/log/subsite-install.log 2>&1 &
