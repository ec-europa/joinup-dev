#!/bin/bash

# Borrowed from https://github.com/docker-library/mysql/blob/master/template/docker-entrypoint.sh
mysql_log() {
  local type="$1"; shift
  printf '%s [%s] [/docker-entrypoint-initdb.d/restore.sh]: %s\n' "$(date --rfc-3339=seconds)" "$type" "$*"
}
mysql_note() {
  mysql_log Note "$@"
}

if [ "${DOCKER_RESTORE_PRODUCTION}" = "yes" ]; then
  mysql_note "Restoring MySQL database from production."
  mysql -u root joinup < /db/mysql/dump/mysql.sql
  mysql_note "Restored MySQL database from production."
else
  mysql_note "Installed an empty MySQL database."
fi
