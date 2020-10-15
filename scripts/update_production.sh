#!/bin/bash

# This script will perform updates on the production environment.

# Define paths.
SCRIPT_PATH="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT=$(realpath ${SCRIPT_PATH}/..)

# Perform the necessary steps for the update
cd ${PROJECT_ROOT}

# Make sure config is writable when performing updates. This depends on the
# following code being present in web/sites/default/settings.php:
# $settings['config_readonly'] = !file_exists(getcwd() . '/../disable-config-readonly');
grep -Fqx '$settings['\''config_readonly'\''] = !file_exists(getcwd() . '\''/../disable-config-readonly'\'');' web/sites/default/settings.php || exit 1

echo "Disabling config_readonly."
touch disable-config-readonly || exit 1

./vendor/bin/drush updatedb --yes --no-post-updates || exit 1
./vendor/bin/drush config:import --yes || exit 1
./vendor/bin/drush updatedb --yes || exit 1
./vendor/bin/drush search-api:reset-tracker --yes || exit 1

echo "Rebuilding node access records."
./vendor/bin/drush php:eval "if(node_access_needs_rebuild()) { node_access_rebuild(); }" || exit 1

echo "Enabling config_readonly."
rm disable-config-readonly || exit 1

# Check if there are any errors or warnings reported. Ignore the warning about
# the update notifications module not being enabled, the updates are monitored
# by the development team.
test $(./vendor/bin/drush status-report --severity=1 --field=title | grep -v "Update notifications" | wc -l) = "0" || (./vendor/bin/drush status-report --severity=1 | grep -v "Update notifications" && exit 1)

exit 0;
