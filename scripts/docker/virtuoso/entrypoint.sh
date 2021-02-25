#!/bin/sh

if [ "${DOCKER_RESTORE_PRODUCTION}" = "yes" ]; then
  BACKUP_PREFIX="JOINUP_FULL_DUMP_" /bin/bash /virtuoso.sh
else
  /bin/bash /virtuoso.sh
fi
