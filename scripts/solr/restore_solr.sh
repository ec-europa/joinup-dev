#!/bin/bash

# Restores a backup of the Solr data.
# This script is intended to be run in the Jenkins pipelines of the acceptance
# and UAT environments.
# For local development it is easier to run the Phing target:
# $ ./vendor/bin/phing restore-databases

SOLR_SERVER_URL="http://localhost:8983/solr"
TIMEOUT=300
TIMEOUT_PATTERN='^[0-9]+$'

# Displays help options.
function show_help {
  echo -e "Usage:   restore_solr.sh [OPTIONS]"
  echo -e "Example: restore_solr.sh --core core0 --snapshot-dir /tmp --snapshot-name core0bak"
  echo -e "Restores a Solr core from a named snapshot located in a directory"
  echo -e "-h, --help          Show this help text"
  echo -e "-c, --core          Solr core to be restored"
  echo -e "-d, --snapshot-dir  The directory containing the snapshot"
  echo -e "-n, --snapshot-name The snapshot name"
  echo -e "-e, --solr-url      Solr server URL. Default: http://localhost:8983/solr"
  echo -e "-t, --timeout       Restore time timeout"
  echo -e "-v, --verbose       If set, verbose logging is on"
}

# Shows a verbose message.
function log {
  if [ "${VERBOSE_LOG}" == "yes"  ]; then
    echo "$(tput setaf 3)$1$(tput sgr0)"
    echo " "
  fi
}

# Exists with a message and an error.
function error {
  echo "$(tput setaf 1)$1$(tput sgr0)";
  exit 1;
}

# Check command line arguments.
while [[ "$1" == -* ]]; do
  case "$1" in
    -h|--help|-\?) show_help; exit 0;;
    -c|--core) shift; CORE=$1; shift;;
    --) shift; break;;
    -v|--verbose) shift; VERBOSE_LOG="yes";;
    -t|--timeout) shift; TIMEOUT=$1; shift;;
    -d|--snapshot-dir) shift; SNAPSHOT_DIR=$1; shift;;
    -n|--snapshot-name) shift; SNAPSHOT_NAME=$1; shift;;
    -u|--solr-url) shift; SOLR_SERVER_URL=$1; shift;;
    -*) echo "invalid option: $1" 1>&2; show_help; exit 1;;
  esac
done

if ! [[ $TIMEOUT =~ ${TIMEOUT_PATTERN} ]] ; then
  error "Timeout needs to be numeric!";
fi

if [ "${CORE}" == '' ] || [ "${SNAPSHOT_DIR}" == '' ] || [ "${SNAPSHOT_NAME}" == '' ]; then
  show_help;
  exit 1;
fi

CORE_EXISTS=`curl -sS "${SOLR_SERVER_URL}/admin/cores?action=STATUS&core=${CORE}&wt=xml" |grep -o '<long name="uptime">'`

if [ "${CORE_EXISTS}" == '' ]; then
  error "Solr '${CORE}' core does not exists on this server!";
fi

# Get the real path.
SNAPSHOT_DIR=$(realpath "${SNAPSHOT_DIR}")

if [ ! -d "${SNAPSHOT_DIR}/snapshot.${SNAPSHOT_NAME}" ]; then
  error "The snapshot ${SNAPSHOT_DIR}/snapshot.${SNAPSHOT_NAME} does't exist.";
fi

# Wipe out the existing index.
log "Wiping out the exiting index of Solr '${CORE}' core."
WIPE_INDEX=`/usr/bin/curl -sS "${SOLR_SERVER_URL}/${CORE}/update?stream.body=<delete><query>*:*</query></delete>&commit=true&wt=xml"`
log "${WIPE_INDEX}"

# Restore the index.
line="${SOLR_SERVER_URL}/${CORE}/replication?command=restore&name=${SNAPSHOT_NAME}&location=${SNAPSHOT_DIR}&wt=xml"
RESTORE_START=`/usr/bin/curl -sS ${line}`
log "${RESTORE_START}$(tput sgr0)"

RESTORE_START_CHECK=`echo ${RESTORE_START} | grep -o '<str name="status">OK</str>'`

if [ "${RESTORE_START_CHECK}" == '' ]; then
  error "Restore failed to start! Please check the output with -v";
fi

SUCCESS=""

COUNTER=0

while [[ "${SUCCESS}" != '<str name="status">success</str>' ]] && [[ ${COUNTER} -lt ${TIMEOUT} ]]
do
  sleep 1

  CHECK_SUCCESS=`/usr/bin/curl -sS "${SOLR_SERVER_URL}/${CORE}/replication?command=restorestatus&name=${SNAPSHOT_NAME}&location=${SNAPSHOT_DIR}&wt=xml"`
  SUCCESS=`echo ${CHECK_SUCCESS} | grep -o '<str name="status">success</str>'`
  log "${CHECK_SUCCESS}"

  COUNTER=$((COUNTER+1))
  log "Timeout counter = ${COUNTER}"
done
