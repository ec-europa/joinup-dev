#!/bin/bash

mkdir -p /opt/docker-solr/drupal/conf
cp -r /solr/conf/* /opt/docker-solr/drupal/conf/
sed -i "/solr.install.dir=/c\solr.install.dir=/opt/solr" /opt/docker-solr/drupal/conf/solrcore.properties
precreate-core joinup /opt/docker-solr/drupal
if [ "${DOCKER_RESTORE_PRODUCTION}" = "yes" ]; then
  /opt/solr/bin/solr start
  sleep 5
  /usr/bin/curl -sS "http://localhost:8983/solr/joinup/replication?command=restore&name=joinup&location=/solr/snapshot&wt=xml"
  (until [[ "$(/usr/bin/curl -sS ''http://localhost:8983/solr/joinup/replication?command=restorestatus'')" =~ \>success\< ]]; do sleep 1; done)
  /opt/solr/bin/solr stop
fi
