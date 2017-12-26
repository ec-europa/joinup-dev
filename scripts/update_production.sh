#!/bin/bash

# This script will perform updates on the production environment.

# Define paths.
SCRIPT_PATH="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT=$(realpath ${SCRIPT_PATH}/..)

# Perform the necessary steps for the update
cd ${PROJECT_ROOT}
./vendor/bin/drush pm:uninstall config_readonly --yes &&
./vendor/bin/drush updatedb --yes &&
./vendor/bin/drush cs-update --discard-overrides --yes &&
./vendor/bin/drush cache-rebuild --yes &&
./vendor/bin/drush pm:enable config_readonly --yes

# Check if any of the steps returned an error.
STATUS=$?
if [ ${STATUS} -ne 0 ]; then
  echo "An error occurred during the update."
  exit ${STATUS}
fi

# Check if there are any errors or warnings reported.
# Ignore the warning about the update notifications module not being enabled,
# the updates are monitored by the development team.
ERROR_COUNT=$(./vendor/bin/drush status-report --severity=1 --field=title | grep -v "Update notifications" | wc -l)
if [ ${ERROR_COUNT} -ne 0 ]; then
  echo "Errors or warnings are reported after the update:"
  ./vendor/bin/drush status-report --severity=1 | grep -v "Update notifications"
  exit 1
fi

echo "Update successfully completed. No errors or warnings reported."
exit 0
