# joinup on mac without docker
Here you will find the steps to run the joinup project on your mac without docker.

## Prerequisites
Brew, Composer, Drush  
Apache and PHP  
[Install Apache & Multiple php versions](https://getgrav.org/blog/macos-catalina-apache-multiple-php-versions)  
Mysql & Apache Virtual Hosts & DnsMasq  
[Install mysql & Apache Virtual Hosts & Dnsmasq](https://getgrav.org/blog/macos-catalina-apache-mysql-vhost-apc)  
Redis  
[Install and config Redis](https://medium.com/@petehouston/install-and-config-redis-on-mac-os-x-via-homebrew-eb8df9a4f298)

## Installation

1 - Uncomment these lines on http.conf file **/usr/local/etc/httpd/httpd.conf**
```
LoadModule vhost_alias_module lib/httpd/modules/mod_vhost_alias.so
Include /usr/local/etc/httpd/extra/httpd-vhosts.conf
````
  
2 - Add virtual-host **/usr/local/etc/httpd/extra/http-vhosts.conf**
```
<VirtualHost *:80>
  ServerName joinup.test
  DocumentRoot "/Users/.../.../joinup-dev/web"
  <Directory "/Users/.../.../joinup-dev/web">
    AllowOverride all
    Require all granted
  </Directory>
</VirtualHost>
 ```
**Please make user ur pathes to the project will match or this won't work**  

3 - Add new host in **/private/etc/hosts**
```
127.0.0.1   joinup.test
```
4 - Restart Apache
````
$ sudo apachectl -k restart
````
5 - **Only if** you have xdebug installed and Check php.ini file for the configuaration.
````
[xdebug]
;zend_extension=“xdebug.so”
xdebug.remote_enable=1
xdebug.remote_autostart=0
xdebug.max_nesting_level=256
;xdebug.collect_params=3
;xdebug.profiler_enabled=1
;xdebug.profiler_output_dir=/tmp/
;xdebug.profiler_enable_trigger=1
````


## Setting up  the project 
1 - Clone the respository of this project
````
$ git clone https://github.com/ec-europa/joinup-dev.git
````

2 - Create file **build.properties.local** in the project with content
```
# The location of the Composer binary.
composer.bin = /usr/local/bin/composer

# Database settings.
drupal.db.name = joinup
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

# Migration.
migration.db.name = joinup6
migration.db.user = joinup6
migration.db.password = joinup6

# Piwik configuration
piwik.db.password = password
piwik.port = 80
piwik.website_id = 1
piwik.url.http = http://piwik.test/

# The credentials of the S3 bucket containing the databases.
# Production db dumps.
#exports.sql.source = joinupv2.0/dumps/prod/joinup-full-20180220.sql

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

2 - Run composer
````
$ composer install
````

3 - Run build dev 
```
$ ./vendor/bin/phing build-dev
```

4 - install and/or relink
```
$ brew unlink unixodbc
$ brew install virtuoso
$ brew unlink virtuoso
$ brew link unixodbc
$ brew link --overwrite virtuoso
```

5 - setup viriuoso
```
$ ./vendor/bin/phing virtuoso-setup
$ ./vendor/bin/phing virtuoso-start
$ ./vendor/bin/phing setup-virtuoso-permissions
```

6 - Run install dev
```  
$ ./vendor/bin/phing install-dev
```

7 - Setup solar and check if its running
```  
$ ./vendor/bin/phing setup-apache-solr
```
[Check Virtuoso](http://localhost:8890/sparql)  
[Check Solr](http://localhost:8983/solr/#/)

8 - Download Databases
````
 $ ./vendor/bin/phing download-databases
````

9 - Rebuild environment
```  
$ ./vendor/bin/phing install-dev
```

10 - Enable developers settings
```  
 $ ./vendor/bin/phing setup-dev   
```
**WARNING** If you get an error:  
*“ Your site configuration active store is currently locked.”*  
comment this line in **/web/sites/default/settings.php**  
$settings['config_readonly'] = !file_exists(getcwd() . '/../disable-config-readonly');

11 - unblock the admin user
````
drush user:unblock
````

12 - create a new admin user 
````
drush uli
````

## Using the gulp
For using the gulp please set your node version to v11.15.0 ( its recommanded to know your current node version so your can put ik back for newer projects ).
To do this check:  
[How do down or upgrade your node version.](https://www.surrealcms.com/blog/how-to-upgrade-or-downgrade-nodejs-using-npm.html)

thenuse the following commands:
````
 $ cd web/themes/joinup/prototype ;
 $ npm install;    
 ````
 check gulp file for all the possible tasks


## Switching between branches
This is you need to when you switch a branch to keep your content up to date
````
./vendor/bin/phing build-dev 
./vendor/bin/phing rebuild-environment 
````
