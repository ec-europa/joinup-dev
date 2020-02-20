#!/bin/bash -ex

# This script will perform updates on the uat environment.

# Define paths.
SCRIPT_PATH="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT=$(realpath ${SCRIPT_PATH}/..)

# Perform the necessary steps for the update
cd ${PROJECT_ROOT}

echo "Disabling config_readonly."
touch disable-config-readonly

./vendor/bin/phing redis-flush
./vendor/bin/drush updatedb --yes --no-post-updates
./vendor/bin/drush config:import --yes
./vendor/bin/drush updatedb --yes
./vendor/bin/drush pm:enable stage_file_proxy --yes

# Todo: Remove this in release 1.58.
# See https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-5837
./vendor/bin/drush pag create rdf_entity

echo "Rebuilding node access records."
./vendor/bin/drush php:eval "if(node_access_needs_rebuild()) { node_access_rebuild(); }"

echo "Enabling config_readonly."
rm disable-config-readonly

echo "Reporting requirements."
./vendor/bin/drush status-report --severity=1 | grep -v "Update notifications"

echo "Update successfully completed."
exit 0
