#!/bin/bash

mkdir -p /opt/docker-solr/drupal/conf
cp -r /solr/conf/* /opt/docker-solr/drupal/conf/
sed -i "/solr.install.dir=/c\solr.install.dir=/opt/solr" /opt/docker-solr/drupal/conf/solrcore.properties
precreate-core drupal_published /opt/docker-solr/drupal
precreate-core drupal_unpublished /opt/docker-solr/drupal
if [ "${DOCKER_RESTORE_PRODUCTION}" = "yes" ]; then
  /opt/solr/bin/solr start
  sleep 5
  /usr/bin/curl -sS "http://localhost:8983/solr/drupal_published/replication?command=restore&name=published&location=/solr/snapshot&wt=xml"
  (until [[ "$(/usr/bin/curl -sS ''http://localhost:8983/solr/drupal_published/replication?command=restorestatus'')" =~ \>success\< ]]; do sleep 1; done)
  /usr/bin/curl -sS "http://localhost:8983/solr/drupal_unpublished/replication?command=restore&name=unpublished&location=/solr/snapshot&wt=xml"
  (until [[ "$(/usr/bin/curl -sS ''http://localhost:8983/solr/drupal_unpublished/replication?command=restorestatus'')" =~ \>success\< ]]; do sleep 1; done)
  /opt/solr/bin/solr stop
fi
