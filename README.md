# Joinup website

This is the source code for https://joinup.ec.europa.eu/

[![Build Status](https://status.continuousphp.com/git-hub/ec-europa/joinup-dev?token=77aa9de5-7fef-40bc-8c48-d6ff70fba9ff)](https://continuousphp.com/git-hub/ec-europa/joinup-dev)

Joinup is a collaborative platform created by the European Commission and
funded by the European Union via the [Interoperability Solutions for European
Public Administrations (ISA)](http://ec.europa.eu/isa/) Programme.

It offers several services that aim to help e-Government professionals share
their experience with each other.  We also hope to support them to find,
choose, re-use, develop and implement interoperability solutions.

The Joinup platform is developed as a Drupal 8 distribution, and therefore
tries to follow the 'drupal-way' as much as possible.

You are free to fork this project to host your own collaborative platform.
Joinup is licensed under the
[EUPL](https://joinup.ec.europa.eu/community/eupl/og_page/eupl), which is
compatible with the GPL.

## Contributing
See our [contributors guide](.github/CONTRIBUTING.md).

## Running your own instance of Joinup

There are two ways to run Joinup. With `docker` and `docker-compose` and building a local installation.

### Docker

To start with docker, please, check the separated [README file](resources/docker/README.md).

### Local installation

To run Joinup locally, below is a list of requirements and instructions.

#### On macOS without Docker installation
To start on macOS without Docker, please, check the separated [README file](resources/mac/README.md).

#### Requirements
* A regular LAMP stack running PHP 7.1.0 or higher
* Virtuoso 7 (Triplestore database)
* Apache Solr

#### Dependency management and builds

We use Drupal composer as a template for the project.  For the most up-to-date
information on how to use Composer, build the project using Phing, or on how to
run the Behat test, please refer directly to the documention of
[drupal-composer](https://github.com/drupal-composer/drupal-project).

#### Initial setup

* Clone the repository.

    ```
    $ git clone https://github.com/ec-europa/joinup-dev.git
    ```

* Use [composer](https://getcomposer.org/) to install the dependencies.

    ```
    $ cd joinup-dev
    $ composer install
    ```

* Install Solr. If you already have Solr installed you can configure it manually
  by [following the installation
  instructions](http://cgit.drupalcode.org/search_api_solr/plain/INSTALL.txt?h=8.x-1.x)
  from the Search API Solr module. Or you can execute the following command to
  download and configure a local instance of Solr. It will be installed in the
  folder `./vendor/apache/solr`.

    ```
    $ ./vendor/bin/phing setup-apache-solr
    ```

* Install Virtuoso. For basic instructions, see [setting up
  Virtuoso](https://github.com/ec-europa/rdf_entity/blob/8.x-1.x/README.md).
  Due to [a bug in Virtuoso 6](https://github.com/openlink/virtuoso-opensource/issues/303) it is recommended to use Virtuoso 7.
  During installation some RDF based taxonomies will be imported from the `resources/fixtures` folder.
  Make sure Virtuoso can read from this folder by adding it to the `DirsAllowed`
  setting in your `virtuoso.ini`. For example:

    ```
    DirsAllowed = /var/www/joinup/resources/fixtures, /usr/share/virtuoso-opensource-7/vad
    ```

* Install [Selenium](https://github.com/SeleniumHQ/docker-selenium/blob/master/README.md).
  The simplest way of doing this is using Docker to install and run it with a
  single command. This will download all necessary files and start the browser
  in the background in headless mode:

    ```
    $ docker run -d -p 4444:4444 --network=host selenium/standalone-chrome
    ```

* Point the document root of your webserver to the 'web/' directory.

#### Create a local build properties file
Create a new file in the root of the project named `build.properties.local
using your favourite text editor:

```
$ vim build.properties.local
```

This file will contain configuration which is unique to your development
machine. This is mainly useful for specifying your database credentials and the
username and password of the Drupal admin user so they can be used during the
installation.

Because these settings are personal they should not be shared with the rest of
the team. Make sure you never commit this file!

All options you can use can be found in the `build.properties.dist` file. Just
copy the lines you want to override and change their values. Do not copy the
entire `build.properties.dist` file, since this would override all options.

Example `build.properties.local`:

```
# The location of the Composer binary.
composer.bin = /usr/bin/composer

# The location of the Virtuoso console (Debian / Ubuntu).
isql.bin = /usr/bin/virtuoso-isql
# The location of the Virtuoso console (Arch Linux).
isql.bin = /usr/bin/virtuoso-isql
# The location of the Virtuoso console (Redhat / Fedora / OSX with Homebrew).
isql.bin = /usr/local/bin/isql

# SQL database settings.
drupal.db.name = my_database
drupal.db.user = root
drupal.db.password = hunter2

# SPARQL database settings.
sparql.dsn = localhost
sparql.user = my_username
sparql.password = qwerty123

# Admin user.
drupal.admin.username = admin
drupal.admin.password = admin

# The base URL to use in tests.
drupal.base_url = http://joinup.local

# Verbosity of Drush commands. Set to 'yes' for verbose output.
drush.verbose = yes
```


#### Build the project

Execute the [Phing](https://www.phing.info/) target `build-dev` to build a
development instance, then install the site with `install-dev`:

```
$ ./vendor/bin/phing build-dev
$ ./vendor/bin/phing install-dev
```


#### Run the tests

Run the Behat test suite to validate your installation.

```
$ cd tests
$ ./behat
```

During development you can enable Behat test screen-shots by uncomment this line in `tests/features/bootstrap/FeatureContext.php`:

```php
  // use \Drupal\joinup\Traits\ScreenShotTrait;
```

and use the `pretty` formatter instead of `progress`, in `tests/behat.yml`:

```yaml
  formatters:
    pretty: ~
```

Also run the PHPUnit tests, from the web root.

```
$ cd web
$ ../vendor/bin/phpunit
```


### Frontend development

See the [readme](web/themes/joinup/README.md) in the theme folder.


### Technical details

* In [Rdf draft module](web/modules/custom/rdf_entity/rdf_draft/README.md)
there is information on handling draft in CRUD operations for rdf entities.
* In [Joinup notification module](web/modules/custom/joinup_notification/README.md)
there is information on how to handle notifications in Joinup.
* In [Joinup core module](web/modules/custom/joinup_core/README.md) there is
information on how to handle and create workflows.

### Checking live site analytics.
In order to check the analytics when it comes to page visits and downloads, a local MATOMO instance is needed.
#### Requirements
* A local LAMP stack as you have for Joinup.
* Set up a local apache site to a directory of choice.
* Set up a local database and its user with full access to the database.
#### Installation
```bash
; Download the MATOMO package to the tmp folder.
$ cd /tmp && wget https://builds.matomo.org/piwik.zip

; Extract the package.
$ unzip piwik.zip

; Move the extracted foldre to your local server directory.
; In the following example command, it is assumed that the directory chosen for the
; MATOMO instance, is `/var/www/html/matomo`.
$ mv piwik /var/www/html/matomo
```
Now, visit your configured webserver domain e.g. "http://local.matomo.instance/" and follow the onscreen instructions to
set up the server, ensure you meet the requirements, and choose the admin account.

In the end of the configuration wizard, also enter the details of the website that you want to be tracked.

Log in to the MATOMO and click the gear icon and then under "Personal" click "Settings".
Scroll down to find the authentication token and copy it.

On your settings.local.php add the following settings:
```php
$config['matomo.settings']['site_id'] = '1';
// The MATOMO instance url. Note that the trailing url should be there.
$config['matomo.settings']['url_http'] = 'http://matomo.test/';
$config['matomo.settings']['url_https'] = '';
// The authentication token you copied from the instance.
$config['matomo_reporting_api.settings']['token_auth'] = '1234567890abcdefg';
```

Finally, on your `matomo.settings.yml`, change the `request_path_mode` from 2 to 0.
This will enable the data tracking.

NOTICE: This does not track search results as those are handled by the oe_webtools.

#### Calculating results
When visiting some pages, the visits will be logged. However, under the "Behaviour" section, no results will be shown as
these need to be calculated and archived first. Since MATOMO is based on a cron job, a manual trigger has to happen.

Under the `/var/www/html/matomo` (or the directory you have configured), there is the `console` executable. Run the
following command to re-calculate all statistics:
```bash
./console core:archive --force-all-websites --force-all-periods=315576000 --force-date-last-n=1000
```
The above command will recalculate statistics fo all sites if you have more than one.

#### Usual problems
When accessing one of the reports under the "Behaviour" section, the default date range is not going to show any
results. In order to show results, change the date range to the current month.
