#!/bin/bash -ex

# This script will perform updates on the uat environment.

# Define paths.
SCRIPT_PATH="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT=$(realpath ${SCRIPT_PATH}/..)

# Perform the necessary steps for the update
cd ${PROJECT_ROOT}

echo "Disabling config_readonly."
touch disable-config-readonly

# Flush Redis cache if enabled.
test "$(./vendor/bin/drush eval 'print \Drupal\Core\Site\Settings::get("cache")["default"];')" = "cache.backend.redis" || ./vendor/bin/run redis:flush-all
# Truncate cache_* tables.
for table in `./vendor/bin/drush sql:query "SHOW TABLES LIKE 'cache\_%'"`; do ./vendor/bin/drush sql:query "TRUNCATE $table"; done
./vendor/bin/drush deploy
./vendor/bin/drush pm:enable stage_file_proxy --yes

echo "Rebuilding node access records."
./vendor/bin/drush php:eval "if(node_access_needs_rebuild()) { node_access_rebuild(); }"

echo "Enabling config_readonly."
rm disable-config-readonly

echo "Reporting requirements."
./vendor/bin/drush status-report --severity=1 | grep -v "Update notifications"

echo "Update successfully completed."
exit 0
