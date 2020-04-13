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

1. Clone the respository of this project

   ```bash
   $ git clone https://github.com/ec-europa/Joinup-dev.git
   ```

2. Create file **build.properties.local** in the project with content
   ```
   # The location of the Composer binary.
   composer.bin = /usr/local/bin/composer

   # Database settings.
   drupal.db.name = Joinup
   drupal.db.user = root
   drupal.db.password =

   # Admin user.
   drupal.admin.username = admin
   drupal.admin.password = admin

   # The base URL to use in Behat tests.
   behat.base_url = http://joinup.test/
   drupal.base_url = http://joinup.test/

   # Paths
   isql.bin = /usr/local/bin/isql
   sparql.dsn = localhost
   sparql.user = dba
   sparql.password = dba

   # Piwik configuration
   piwik.db.password = password
   piwik.port = 80
   piwik.website_id = 1
   piwik.url.http = http://piwik.test/

   # The credentials of the S3 bucket containing the databases.
   # Production db dumps.
   #exports.sql.source = joinupv2.0/dumps/prod/Joinup-full-20180220.sql

   # Virtuoso
   virtuoso.binary = /usr/local/bin/virtuoso-t

   # Solr
   solr.download.url = https://archive.apache.org/dist/lucene/solr/7.7.1/solr-7.7.1.tgz

   # ASDA settings
   asda.username = ''
   asda.password = ''
   exports.virtuoso.source = ''
   exports.sql.source = ''
   exports.solr.filename = ''
   exports.solr.source = ''
   exports.s3.bucket = ''
   exports.s3.key = ''
   exports.s3.secret = ''
   ```

**! Important: For the ASDA settings please contact your local developer !**  

3. Run composer

   ```bash
   $ composer install
   ```

4. Run `build-dev`

   ```bash
   $ ./vendor/bin/phing build-dev
   ```

5. Install and/or relink

   ```bash
   $ brew unlink unixodbc
   $ brew install virtuoso
   $ brew unlink virtuoso
   $ brew link unixodbc
   $ brew link --overwrite virtuoso
   ```

6. Setup Virtuoso

   ```bash
   $ ./vendor/bin/phing virtuoso-setup
   $ ./vendor/bin/phing virtuoso-start
   $ ./vendor/bin/phing setup-virtuoso-permissions
   ```

  [Check Virtuoso](http://localhost:8890/sparql)

7. Run `install-dev`

   ```bash
   $ ./vendor/bin/phing install-dev
   ```

8. Setup Solr and check if it's running

   ```bash
    $ ./vendor/bin/phing setup-apache-solr
    ```

   [Check Solr](http://localhost:8983/solr/#/)

9. Download production Databases

   ```bash
   $ ./vendor/bin/phing download-databases
   ```

10. Rebuild environment

   ```bash
   $ ./vendor/bin/phing rebuild-environment
   ```

11. Enable developers settings

   ```bash
   $ ./vendor/bin/phing setup-dev
   ```

   **WARNING** If you get an error: "Your site configuration active store is
   currently locked.”, comment this line in **/web/sites/default/settings.php**:
   ```php
   $settings['config_readonly'] = !file_exists(getcwd() . '/../disable-config-readonly');
   ```

12. Unblock the admin user

   ```bash
   $ drush user:unblock
   ```

13. Login with the admin user

   ```bash
   $ drush uli
   ```

## Switching between branches

This is needed when you'll have to switch a branch and keep your content up to
date:

```bash
$ ./vendor/bin/phing build-dev
$ ./vendor/bin/phing rebuild-environment
```
