#!/bin/bash

# This script will build an RPM package intended for deploying on UAT.

# Define paths.
if [ -z ${COMPOSER_PATH} ]; then
  COMPOSER_PATH=/usr/local/bin/composer
fi
SCRIPT_PATH="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT=$(realpath ${SCRIPT_PATH}/../..)
BUILD_ROOT=${PROJECT_ROOT}/tmp/rpmbuild

# Determine a version string that identifies the current version. First check if
# a tag is available for the checked out revision. If that fails, construct a
# version string consisting of the branch name and the ref.
cd ${PROJECT_ROOT}

# Clean up existing builds.
if [ -d ${BUILD_ROOT} ]; then
  chmod -R u+w ${BUILD_ROOT}
  rm -rf ${BUILD_ROOT} || exit 1
fi

mkdir -p ${BUILD_ROOT}

# Create a fresh build root containing the scaffolding files.
cp -r ${PROJECT_ROOT}/resources/rpmbuild/* ${BUILD_ROOT} || exit 1

SOURCES_DIR=${BUILD_ROOT}/SOURCES
JOINUP_DIR=${SOURCES_DIR}/Joinup-${BUILD_VERSION}

mkdir -p ${JOINUP_DIR} || exit 1

# Build the site.
sudo ${COMPOSER_PATH} self-update || exit 1
COMPOSER_DISCARD_CHANGES=true ${COMPOSER_PATH} install --no-interaction --prefer-dist || exit 1
./vendor/bin/run joinup:compile-scss || exit 1


# Collect the source files for the package.
cp -r build* composer.* VERSION config/ drush/ resources/ scripts/ src/ vendor/ web/ ${JOINUP_DIR} || exit 1

# Replace files and folders with production symlinks.
rm -rf ${JOINUP_DIR}/web/sites/default/settings.php
rm -rf ${JOINUP_DIR}/web/sites/default/files
cp -r ${SOURCES_DIR}/template/* ${JOINUP_DIR}/web || exit 1
rm -r ${SOURCES_DIR}/template || exit 1

# Remove unneeded files.
rm -rf ${JOINUP_DIR}/build.*local*
rm -rf ${JOINUP_DIR}/web/themes/joinup/prototype

# Output the version number in a file that will be appended to the HTTP headers.
echo X-build-id: $BUILD_VERSION > ${SOURCES_DIR}/buildinfo.ini

# Tar up the source files.
tar -czf ${SOURCES_DIR}/Joinup-${BUILD_VERSION}.tar.gz -C ${SOURCES_DIR} Joinup-${BUILD_VERSION}/ || exit 1
rm -rf ${JOINUP_DIR} || exit 1

echo "Build is available in ${BUILD_ROOT}."
exit 0
