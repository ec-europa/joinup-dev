#!/bin/sh

#export BUILD_VERSION=v1.0
buildroot=`pwd`

cd ${buildroot}/SOURCES

git clone git@github.com:ec-europa/erxs.git Euraxess-$BUILD_VERSION
cd Euraxess-$BUILD_VERSION
git checkout develop
echo X-build-id: $BUILD_VERSION > ./buildinfo.ini
cd docroot
cp -R ../../template/* ./
cd ..
mv docroot Euraxess-$BUILD_VERSION
tar -cvvzf ../Euraxess-$BUILD_VERSION.tar.gz Euraxess-$BUILD_VERSION/
rm -rf Euraxess-$BUILD_VERSION
mkdir Euraxess-updater-$BUILD_VERSION
mv scripts Euraxess-updater-$BUILD_VERSION
mv dependencies Euraxess-updater-$BUILD_VERSION
tar -cvvzf ../Euraxess-updater-$BUILD_VERSION.tar.gz Euraxess-updater-$BUILD_VERSION/

cd ${buildroot}/SPECS
rpmbuild -ba euraxess.spec --define "_topdir ${buildroot}"
