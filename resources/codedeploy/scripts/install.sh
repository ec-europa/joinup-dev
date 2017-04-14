#!/bin/bash
cd /srv/project

while [ ! -f /usr/local/etc/subsite/subsite.ini ]
do
  sleep 2
done

aws s3 cp s3://ec-europa-db-dump/joinup/joinup_prod_cleaned.sql ./tmp/d6-joinup.sql --region=eu-west-1
aws s3 cp s3://ec-europa-db-dump/joinup/d6-joinup-test.sql ./tmp/d6-joinup-test.sql --region=eu-west-1
chown -R www-data:www-data /srv/project
# Background and detach to work around time constrains of AWS CodeDeploy.
nohup sudo -u www-data vendor/bin/phing -propertyfile /usr/local/etc/subsite/subsite.ini install setup-acceptance  >> /var/log/subsite-install.log 2>&1 &
