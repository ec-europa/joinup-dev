# Joinup on macOS without Docker

Here you will find the steps to run the Joinup project on your mac without
Docker.

## Prerequisites

- Brew, Composer, Drush, Apache and PHP: [Install Apache & Multiple php
versions](https://getgrav.org/blog/macos-catalina-apache-multiple-php-versions)
- MySql & Apache Virtual Hosts & DnsMasq: [Install mysql & Apache Virtual Hosts
& Dnsmasq](https://getgrav.org/blog/macos-catalina-apache-mysql-vhost-apc)
- Redis: [Install and config
Redis](https://medium.com/@petehouston/install-and-config-redis-on-mac-os-x-via-homebrew-eb8df9a4f298)

## Installation

1. Uncomment these lines on http.conf file **/usr/local/etc/httpd/httpd.conf**

   ```
   LoadModule vhost_alias_module lib/httpd/modules/mod_vhost_alias.so
   Include /usr/local/etc/httpd/extra/httpd-vhosts.conf
   ```
  
2. Add virtual-host **/usr/local/etc/httpd/extra/http-vhosts.conf**

   ```
   <VirtualHost *:80>
     ServerName joinup.test
     # Replace with your project path.
     DocumentRoot "/Users/.../.../joinup-dev/web"
     # Replace with your project path.
     <Directory "/Users/.../.../joinup-dev/web">
       AllowOverride all
       Require all granted
     </Directory>
   </VirtualHost>
   ```

3. Add new host in **/private/etc/hosts**

   ```
   127.0.0.1   joinup.test
   ```

4. Restart Apache

   ```bash
   $ sudo apachectl -k restart
   ```

5. **Only if** you have XDebug installed check php.ini file for the config:

   ```
   [xdebug]
   ;zend_extension=“xdebug.so”
   xdebug.remote_enable=1
   xdebug.remote_autostart=0
   xdebug.max_nesting_level=256
   ;xdebug.collect_params=3
   ;xdebug.profiler_enabled=1
   ;xdebug.profiler_output_dir=/tmp/
   ;xdebug.profiler_enable_trigger=1
   ```

## Setting up the project

### Clone the repository

```bash
$ git clone https://github.com/ec-europa/joinup-dev.git
```

### Create a local task runner configuration file

In order to override any configuration of the task runner (`./vendor/bin/run`),
create a `runner.yml` file in the project's top directory. You can override
there any default runner configuration, or any other declared in
`./resources/runner` files or in `runner.yml.dist`. Note that the `runner.yml`
file is not under VCS control.

### Environment variables

Sensitive data will be stored in [environment variables](
https://en.wikipedia.org/wiki/Environment_variable). See `.env.dist` for
details.

**Important!** For the ASDA settings please contact your local developer

## Build the codebase

```bash
$ composer install
```

### Install and/or relink

```bash
$ brew unlink unixodbc
$ brew install virtuoso
$ brew unlink virtuoso
$ brew link unixodbc
$ brew link --overwrite virtuoso
```

### Setup Virtuoso

```bash
$ ./vendor/bin/run virtuoso:setup
$ ./vendor/bin/run virtuoso:start
```

[Check Virtuoso](http://localhost:8890/sparql)

### Run `toolkit:install-clean`

```bash
$ ./vendor/bin/run toolkit:install-clean
```

### Setup Solr and check if it's running

```bash
$ ./vendor/bin/run solr:setup
``` 

[Check Solr](http://localhost:8983/solr/#/)

### Download production databases

```bash
$ ./vendor/bin/run dev:download-databases
```

### Rebuild environment

```bash
$ ./vendor/bin/run toolkit:install-clone
```

### Unblock the admin user

```bash
$ drush user:unblock
```

### Login with the admin user

```bash
$ drush uli
```

## Switching between branches

This is needed when you'll have to switch a branch and keep your content up to
date:

```bash
$ ./vendor/bin/composer install
$ ./vendor/bin/run toolkit:install-clone
```
