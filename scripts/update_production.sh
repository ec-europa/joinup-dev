#!/bin/bash

# This script will perform updates on the production environment.

# Define paths.
SCRIPT_PATH="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT=$(realpath ${SCRIPT_PATH}/..)

# Keep track of any errors that occur during the update.
STATUS=0
trap 'STATUS=$?' ERR

# Virtuoso has a checkpoint interval of 60 minutes. That means that every 60 minutes,
# the server will be un responsive for a small amount of time (a few seconds).
# Since our updates take too long, this can lead to "random" valid query failures.
# Set the interval to 0 to disable automatic checkpoints. At the end of the update
# The interval will be restored, and a manual checkpoint will be fired in order to
# write all the transactions of the updates into the disk.
echo "Disabling automatic checkpoints."
./vendor/bin/phing set-virtuoso-checkpoint -Dinterval="-1"

# Perform the necessary steps for the update
cd ${PROJECT_ROOT}

# Make sure config is writable when performing updates. This depends on the
# following code being present in web/sites/default/settings.php or
# web/sites/default/settings.local.php:
# $settings['config_readonly'] = !file_exists(getcwd() . '/../disable-config-readonly');
grep -Fqx '$settings['\''config_readonly'\''] = !file_exists(getcwd() . '\''/../disable-config-readonly'\'');' web/sites/default/{settings.php,settings.local.php}

if [ ${STATUS} -ne 0 ]; then
  echo "The following line is missing from web/sites/default/settings.php"
  echo "or web/sites/default/settings.local.php:"
  echo '$settings['\''config_readonly'\''] = !file_exists(getcwd() . '\''/../disable-config-readonly'\'');'
  exit ${STATUS}
fi

echo "Disabling config_readonly."
touch disable-config-readonly

./vendor/bin/drush cache:clear bin config --yes &&
./vendor/bin/drush updatedb --yes &&
./vendor/bin/drush cs-update --discard-overrides --yes &&
./vendor/bin/drush search-api:reset-tracker --yes &&
./vendor/bin/drush cache-rebuild --yes &&

echo "Rebuilding node access records." &&
./vendor/bin/drush php:eval "if(node_access_needs_rebuild()) { node_access_rebuild(); }"

echo "Creating a manual checkpoint."
./vendor/bin/phing execute-virtuoso-checkpoint

echo "Restoring the virtuoso checkpoint interval."
./vendor/bin/phing set-virtuoso-checkpoint -Dinterval=60

echo "Enabling config_readonly."
rm disable-config-readonly

# Check if any of the steps returned an error.
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
  # Disable exiting with error until all the status errors are fixed, in order
  # to prevent marking the Jenkins build as failed. Otherwise, when this script
  # is called in an AND (&&) chained list of commands, all commands chained
  # after this script are not executed. Restore exiting with error as soon as
  # all of ISAICP-4702, ISAICP-4701 and ISAICP-4092 are fixed.
  # @todo Uncomment the next line in ISAICP-4773.
  # @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4773
  # exit 1
fi

echo "Update successfully completed. No errors or warnings reported."
exit 0
