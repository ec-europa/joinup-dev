#===============================================================================
# Copyright 2017 European Commission
# Name: joinup.spec
#-------------------------------------------------------------------------------
# $Id: joinup.spec,v 1.0 2017/9/13 rvanhoudt Exp $
#-------------------------------------------------------------------------------
# Purpose: RPM Spec file for joinup website
# Version 1.00:13 Sep 2017 Created.
#===============================================================================

# No debuginfo:
%define debug_package %{nil}

%define name      Joinup
%define summary   Joinup Main website
%define version   %(echo $BUILD_VERSION)
%define release   Base
%define license   EUPL
%define group     Website
%define source0   %{name}-%{version}.tar.gz
%define url       http://www.joinup.ey
%define vendor    European Commission
%define packager  Rudi Van Houdt
#%define buildroot %{_tmppath}/%{name}-root
%define _prefix   /web/content/joinup

Name:      %{name}
Summary:   %{summary}
Version:   %{version}
Release:   %{release}
License:   %{license}
Group:     %{group}
Source0:   %{source0}
BuildArch: noarch
Requires:  filesystem, bash, grep
Provides:  %{name}
URL:       %{url}
Buildroot: %{buildroot}
AutoReqProv: no

%description
Deploying website to different environments

%changelog
* Wed Sep 13 2017 Joinup - European Commission
+ initial creation

%prep
%setup -c -n %{name}-%{version}

%build

%install
rm -rf ${RPM_BUILD_ROOT}
install -d ${RPM_BUILD_ROOT}/%{_prefix}
cd ..
mkdir -p ${RPM_BUILD_ROOT}/%{_prefix}/%{name}-%{version}
cp -r %{name}-%{version}/%{name}-%{version} ${RPM_BUILD_ROOT}/%{_prefix}/

%post
echo "--------------------------------------------------------"
echo "      Deploy %{name}-%{version} on the server"
echo "--------------------------------------------------------"

rm -rf %{_prefix}/current
ln -s %{_prefix}/%{name}-%{version}/ %{_prefix}/current
cd %{_prefix}
ls -td1 Join* | tail -n +4 | xargs sudo rm -rf

cd current
mkdir -p config/sync
cd ..

IS_USER=`cat /etc/passwd |grep '%{_prefix}' | tr ":" "\n" | head -n 1`

if [ $IS_USER ]; then
  echo "Changing access rights of deployed app !"
  chown $IS_USER:apache %{name}-%{version} -R
fi

%files
%{_prefix}/%{name}-%{version}/*
%{_prefix}/%{name}-%{version}/.dockerignore
%{_prefix}/%{name}-%{version}/.env.dist
%{_prefix}/%{name}-%{version}/.opts.yml
