#!/bin/bash

# This script will build an RPM package intended for deploying on production.

# Define paths.
SCRIPT_PATH="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT=$(realpath ${SCRIPT_PATH}/../..)
BUILD_ROOT=${PROJECT_ROOT}/tmp/rpmbuild

# Determine a version string that identifies the current version. First check if
# a tag is available for the checked out revision. If that fails, construct a
# version string consisting of the branch name and the ref.
cd ${PROJECT_ROOT}
BUILD_VERSION=$(git describe --exact-match --tags HEAD 2>/dev/null)

if [ $? -ne 0 ]; then
  BUILD_VERSION=$(git symbolic-ref --short HEAD)-$(git rev-parse HEAD)
fi

# Download composer dependencies.
composer install --no-dev

# Build the site.
./vendor/bin/phing build-dist

# Clean up existing builds.
rm -rf ${BUILD_ROOT}
mkdir -p ${BUILD_ROOT}

# Create a fresh build root containing the scaffolding files.
cp -r ${PROJECT_ROOT}/resources/rpmbuild ${BUILD_ROOT}

# Collect the source files for the package.
SOURCES_DIR=${BUILD_ROOT}/SOURCES
JOINUP_DIR=${SOURCES_DIR}/Joinup-${BUILD_VERSION}
mkdir -p ${JOINUP_DIR}

cp -r config/ src/ vendor/ web/ ${JOINUP_DIR}

# Replace environment specific files and folders with production symlinks.
rm -rf ${JOINUP_DIR}/web/sites/default/settings.php
rm -rf ${JOINUP_DIR}/web/sites/default/files
cp -r ${SOURCES_DIR}/template/* ${JOINUP_DIR}/web
rm -r ${SOURCES_DIR}/template

# Remove unneeded files.
# Todo: verify with Francesco if this is OK.
rm -rf ${JOINUP_DIR}/web/themes/joinup/prototype

# Output the version number in a file that will be appended to the HTTP headers.
echo X-build-id: $BUILD_VERSION > ${SOURCES_DIR}/buildinfo.ini

# Tar up the source files.
tar -czf ${SOURCES_DIR}/Joinup-${BUILD_VERSION}.tar.gz -C ${SOURCES_DIR} Joinup-${BUILD_VERSION}/
rm -rf ${JOINUP_DIR}

# Todo: The following is for Rudi :)
exit 0

# Copy files to the production build storage of the EC.
# Todo: This should be a separate step so this script can also be used outside
# of the European Commission.

cd ${BUILD_ROOT}/SPECS
rpmbuild -ba joinup.spec --define "_topdir ${BUILD_ROOT}"

cd ${BUILD_ROOT}/RPMS
cp -R noarch /mnt/shared/distribution/
rm -rf noarch
cd /mnt/shared/distribution/
createrepo . --no-database
