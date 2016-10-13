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

### Requirements
* A regular LAMP stack
* Virtuoso (Triplestore database)
* SASS compiler
* Apache Solr

### Dependency management and builds

We use Drupal composer as a template for the project.  For the most up-to-date
information on how to use Composer, build the project using Phing, or on how to
run the Behat test, please refer directly to the documention of
[drupal-composer](https://github.com/drupal-composer/drupal-project).

### Initial setup

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
  Virtuoso](/web/modules/custom/rdf_entity/README.md). During installation some
  RDF based taxonomies will be imported from the `resources/fixtures` folder.
  Make sure Virtuoso can read from this folder by adding it to the `DirsAllowed`
  setting in your `virtuoso.ini`. For example:

    ```
    DirsAllowed = /var/www/joinup/resources/fixtures, /usr/share/virtuoso-opensource-7/vad
    ```

* Install the official [SASS compiler](https://github.com/sass/sass). This
  depends on Ruby being installed on your system.

    ```
    $ gem install sass
    ```

* Point the document root of your webserver to the 'web/' directory.

### Create a local build properties file
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
# The location of the Virtuoso console (Redhat / Fedora).
isql.bin = /usr/local/bin/isql

# SQL database settings.
drupal.db.name = my_database
drupal.db.user = root
drupal.db.password = hunter2

# SPARQL database settings.
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


### Build the project

Execute the [Phing](https://www.phing.info/) target `build-dev` to build a
development instance, then install the site with `install-dev`:

```
$ ./vendor/bin/phing build-dev
$ ./vendor/bin/phing install-dev
```


### Run the tests

Run the Behat test suite to validate your installation.

```
$ cd tests
$ ./behat
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
there are information on handling draft in CRUD operations for rdf entities.
* In [Joinup notification module](web/modules/custom/joinup_notification/README.md)
there are information on how to handle notifications in Joinup.