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

# Clean up existing builds.
chmod -R u+w ${BUILD_ROOT} 2>/dev/null
rm -rf ${BUILD_ROOT} || exit 1

# Create a fresh build root containing the scaffolding files.
cp -r ${PROJECT_ROOT}/resources/rpmbuild ${BUILD_ROOT} || exit 1

SOURCES_DIR=${BUILD_ROOT}/SOURCES
JOINUP_DIR=${SOURCES_DIR}/Joinup-${BUILD_VERSION}

mkdir -p ${JOINUP_DIR} || exit 1

cp build* ${JOINUP_DIR}
cp composer* ${JOINUP_DIR}

# Download composer dependencies.
/usr/bin/composer install --no-dev || exit 1

# Build the site.
./vendor/bin/phing build-dist || exit 1

# Collect the source files for the package.
cp -r config/ resources/ scripts/ src/ vendor/ web/ ${JOINUP_DIR} || exit 1

# Replace environment specific files and folders with production symlinks.
rm -rf ${JOINUP_DIR}/web/sites/default/settings.php || exit 1
rm -rf ${JOINUP_DIR}/web/sites/default/files || exit 1
cp -r ${SOURCES_DIR}/template/* ${JOINUP_DIR}/web || exit 1
rm -r ${SOURCES_DIR}/template || exit 1

# Remove unneeded files.
rm -rf ${JOINUP_DIR}/web/themes/joinup/prototype || exit 1

# Output the version number in a file that will be appended to the HTTP headers.
echo X-build-id: $BUILD_VERSION > ${SOURCES_DIR}/buildinfo.ini

# Tar up the source files.
tar -czf ${SOURCES_DIR}/Joinup-${BUILD_VERSION}.tar.gz -C ${SOURCES_DIR} Joinup-${BUILD_VERSION}/ || exit 1
rm -rf ${JOINUP_DIR} || exit 1

# Todo: Exiting here, the remainder is for Rudi :)
echo "Build is available in ${BUILD_ROOT}."
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
