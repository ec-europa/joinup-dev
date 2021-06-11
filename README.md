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

To start with docker, please, check the separated [README file](docs/docker/README.md).

### Local installation

To run Joinup locally, below is a list of requirements and instructions.

#### On macOS without Docker installation
To start on macOS without Docker, please, check the separated [README file](resources/mac/README.md).

#### Requirements
* A regular LAMP stack running PHP 7.1.0 or higher
* Virtuoso 7 (Triplestore database)
* Apache Solr

#### Dependency management and builds

We use Drupal composer as a template for the project. For the most up-to-date
information on how to use Composer, build the project using the Task Runner, or
on how to run the Behat test, please refer directly to the documentation of each
used tool.

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
  from the Search API Solr module. Or you can execute the following commands to
  download and configure a local instance of Solr. It will be installed in the
  folder `./vendor/apache/solr`.

    ```
    $ ./vendor/bin/run solr:download-bin
    $ ./vendor/bin/run solr:config
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

This file will contain the configuration which is unique to your development
machine. This is mainly useful for specifying your database credentials and the
username and password of the Drupal admin user, so they can be used during the
installation.

Because these settings are personal they should not be shared with the rest of
the team. Make sure you never commit this file!

All options you can use can be found in the `build.properties` file. Just copy
the lines you want to override and change their values. Do not copy the entire
`build.properties` file, since this would override all options.


#### Create a local task runner configuration file

In order to override any configuration of the task runner (`./vendor/bin/run`),
create a `runner.yml` file in the project's top directory. You can override
there any default runner configuration, or any other declared in
`./resources/runner` files or in `runner.yml.dist`. Note that the `runner.yml`
file is not under VCS control.

#### Setup environment variables

Sensitive data will be stored in [environment variables](
https://en.wikipedia.org/wiki/Environment_variable). See `.env.dist` for
details. To adapt these values to your own environment, create a `.env` file
that contains only the overridden values. For a local development environment
this could look like the following:

```bash
DRUPAL_BASE_URL=http://my-base-url.local
DRUPAL_DATABASE_USERNAME=my-database-username
DRUPAL_DATABASE_PASSWORD=my-database-password
DRUPAL_DATABASE_HOST=localhost
DRUPAL_HASH_SALT=some-unique-random-string-like-37h+2BQEQx83YLa/uFdsfG55

SOLR_CORE_PUBLISHED_URL=http://localhost:8983/solr
SOLR_CORE_UNPUBLISHED_URL=http://localhost:8983/solr

SPARQL_HOST=localhost
REDIS_HOST=localhost

SIMPLETEST_BASE_URL=http://my-base-url.local
SIMPLETEST_DB=mysql://root@localhost:3306/joinup
SIMPLETEST_SPARQL_DB=sparql://localhost:8890
MINK_DRIVER_ARGS_WEBDRIVER=""
DTT_BASE_URL=http://my-base-url.local
DTT_API_URL=http://localhost:4444/wd/hub
DTT_MINK_DRIVER_ARGS="['chrome', null, 'http://localhost:4444/wd/hub']"
```

#### Build the project

Run Composer install to get all dependencies and prepare the code base, then
install the site with `toolkit:install-clean`:

```
$ composer install
$ ./vendor/bin/run toolkit:install-clean
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


### Upgrade process

Joinup offers only _contiguous upgrades_. For instance, if you project is
currently on Joinup `v1.39.2`, and the latest stable version is `v1.42.0`, then
you cannot upgrade directly to the latest version. Instead, you should upgrade
first to `v1.40.0`, second to `v1.40.1` (if exists) and, finally, to `v1.42.0`.

The Joinup update and post-update scripts naming is following this pattern:

`function mymodule_update_0106100() {...}`

or

`function mymodule_post_update_0207503() {...}`

The (post)updated identifier (the numeric part consists in seven digits with the
following meaning:

* The first two digits are the Joinup major version.
* The following three digits are the Joinup minor version.
* The last two digits are an integer that sets the weight within updates or
  post updates from the same extension (module or profile). `00` is the first
  (post)update that applies.

For the above example:

* `function mymodule_update_0106100() {...}`: Was applied in Joinup `v1.61.x` as
  the first update of the `mymodule` module (`01` major version, `061` minor
  version, `00` update weight within the module).
* `function mymodule_post_update_0207503() {...}`: Was applied in Joinup
  `v2.75.x` as the fourth post update of the `mymodule` module (`02` major
  version, `075` minor version, `03` update weight within the module).


### Technical details

* In [Rdf draft module](web/modules/custom/rdf_entity/rdf_draft/README.md)
there is information on handling draft in CRUD operations for rdf entities.
* In [Joinup notification module](web/modules/custom/joinup_notification/README.md)
there is information on how to handle notifications in Joinup.
* In [Joinup core module](web/modules/custom/joinup_core/README.md) there is
information on how to handle and create workflows.
