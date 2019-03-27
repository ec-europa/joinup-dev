#!/bin/bash

cr=$'\r'
SOLR_SERVER_URL="http://localhost:8983/solr"
TIMEOUT=300
re='^[0-9]+$'

# Function to display help options.
function show_help {
 echo -e "Usage:      restore_solr.sh [OPTIONS]"
 echo -e "Example:    restore_solr --core core0 --snapshot-dir /tmp --snapshot-name core0bak"
 echo -e "Restores a Solr core from a named snapshot located in a directory"
 echo -e "-h    --help          Show this help text"
 echo -e "-c    --core          Solr core to be restored"
 echo -e "-d    --snapshot-dir  The directory containing the snapshot"
 echo -e "-n    --snapshot-name The snapshot name"
 echo -e "-u    --solr-url      Solr server URL. Defaults to http://localhost:8983/solr"
 echo -e "-t    --timeout       Restore time timeout"
 echo -e "-v    --verbose       If set, verbose logging is on"
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

if ! [[ $TIMEOUT =~ $re ]] ; then
  echo "$(tput setaf 1)Timeout needs to be numeric !$(tput sgr0)";
  exit 1
fi

if [ "${CORE}" == '' ] || [ "${SNAPSHOT_DIR}" == '' ] || [ "${SNAPSHOT_NAME}" == '' ]; then
  show_help;
  exit 1;
fi

CORE_EXISTS=`curl -sS "${SOLR_SERVER_URL}/admin/cores?action=STATUS&core=${CORE}" |grep -o '<long name="uptime">'`

if [ "${CORE_EXISTS}" == '' ]; then
  echo "$(tput setaf 1)CORE does not exists on this solr server ! please take an other one$(tput sgr0)";
  exit 1;
fi

# Get the real path.
SNAPSHOT_DIR=$(realpath "${SNAPSHOT_DIR}")

if [ ! -d "${SNAPSHOT_DIR}" ]; then
  echo "$(tput setaf 1)The snapshot directory ${SNAPSHOT_DIR} does't exist$(tput sgr0)";
  exit 1;
fi

if [ ! -d "${SNAPSHOT_DIR}/snapshot.${SNAPSHOT_NAME}" ]; then
  echo "$(tput setaf 1)The snapshot ${SNAPSHOT_DIR}/snapshot.${SNAPSHOT_NAME} does't exist$(tput sgr0)";
  exit 1;
fi

# Restore de index.
line="${SOLR_SERVER_URL}/${CORE}/replication?command=restore&name=${SNAPSHOT_NAME}&location=${SNAPSHOT_DIR}"
RESTORE_START=`/usr/bin/curl -sS ${line}`

if [ "${VERBOSE_LOG}" == "yes"  ]; then
  echo "$(tput setaf 3)${RESTORE_START}$(tput sgr0)"
  echo " "
  echo " "
fi

RESTORE_START_CHECK=`echo ${RESTORE_START} | grep -o '<str name="status">OK</str>'`

if [ "${RESTORE_START_CHECK}" == '' ]; then
  echo "$(tput setaf 1)Restore failed to start! Please check the output with -v$(tput sgr0)";
  exit 1;
fi

SUCCESS=""

COUNTER=0

while [[ "${SUCCESS}" != '<str name="status">success</str>' ]] && [[ ${COUNTER} -lt ${TIMEOUT} ]]
do
   sleep 1

   CHECK_SUCCESS=`/usr/bin/curl -sS "${SOLR_SERVER_URL}/${CORE}/replication?command=restorestatus&name=${SNAPSHOT_NAME}&location=${SNAPSHOT_DIR}"`
   SUCCESS=`echo ${CHECK_SUCCESS} | grep -o '<str name="status">success</str>'`

   COUNTER=$((COUNTER+1))

   if [ "${VERBOSE_LOG}" == "yes"  ]; then
     echo "$(tput setaf 3)${CHECK_SUCCESS}$(tput sgr0)"
     echo " "
     echo " "
     echo "$(tput setaf 3)Timeout counter = ${COUNTER}$(tput sgr0)"
     echo " "
     echo " "
   fi
done
