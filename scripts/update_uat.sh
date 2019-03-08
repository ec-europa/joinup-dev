#!/bin/bash

# This script will perform updates on the production environment.

# Define paths.
SCRIPT_PATH="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT=$(realpath ${SCRIPT_PATH}/..)

# Keep track of any errors that occur during the update.
STATUS=0
trap 'STATUS=$?' ERR

# Perform the necessary steps for the update
cd ${PROJECT_ROOT}

echo "Disabling config_readonly."
touch disable-config-readonly

./vendor/bin/drush cache:clear bin config --yes &&
./vendor/bin/drush updatedb --yes &&
./vendor/bin/drush cs-update --discard-overrides --yes &&
./vendor/bin/drush search-api:reset-tracker --yes &&
./vendor/bin/drush cache-rebuild --yes &&

echo "Rebuilding node access records." &&
./vendor/bin/drush php:eval "if(node_access_needs_rebuild()) { node_access_rebuild(); }"

echo "Enabling config_readonly."
rm disable-config-readonly

# Check if any of the steps returned an error.
if [ ${STATUS} -ne 0 ]; then
  echo "An error occurred during the update."
  exit ${STATUS}
fi

echo "Reporting requirements."
./vendor/bin/drush status-report --severity=1 | grep -v "Update notifications"

echo "Update successfully completed."
exit 0
