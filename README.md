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
[EUPL](https://en.wikipedia.org/wiki/European_Union_Public_Licence), which is
compatible with the GPL.


## Running your own instance of Joinup

### Dependencies
* A regular LAMP stack
* Virtuoso (Triplestore database)
* Apache Solr

### Dependency management and builds

We use Drupal composer as a template for the project.  For the most up-to-date
information on how to use Composer, build the project using Phing, or on how to
run the Behat test, please refer directly to the documention of
[drupal-composer](https://github.com/drupal-composer/drupal-project).

### Initial setup

* Clone this repository.  Use [composer](https://getcomposer.org/) to install
* the dependencies.  Install Virtuoso. See [setting up
  Virtuoso](/web/modules/custom/rdf_entity/README.md).
* Set up a Solr search server, using the configuration provided inside the
  `search_api_solr` module. For installation instructions, refer to
  `INSTALL.txt` inside the `search_api_solr` module.
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

# Database settings.
drupal.db.name = my_database
drupal.db.user = root
drupal.db.password = hunter2

# Admin user.
drupal.admin.username = admin
drupal.admin.password = admin

# The base URL to use in Behat tests.
behat.base_url = http://joinup.local

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
$ cd tests; ./behat
```


## Contributing

* You're thinking of setting up your own code repository using the Joinup
  codebase?
* You are about to develop a big feature on top of this codebase?
* You're having trouble installing this project?
* If you want to report an issue?

Use the Github issue queue to get in touch! We'd like to hear about your plans.


## Code quality

We try to keep the quality of this repository as high as possible, and
therefore a few measures are put in place:
* Coding standards are verified.
* Behat tests to avoid regression.

You can [check our current test scenarios here](/tests/features/).

If you plan to make contributions to the Joinup codebase, we kindly ask you to
run the coding standards checks, as well as the Behat test suite before making
a pull request. Also make sure you add test coverage for the functionality
covered in the pull request.

## Phing targets

These are some extra phing targets that will help you setup some things.
* setup-virtuoso-permissions: For this you will need to specify some variables
in your build.properties.local file. These parameters are
  * sparql.host: The host of your virtuoso server
  * sparql.port: The port of your virtuoso server
  * sparql.dsn: The virtuoso odbc alias. Check https://github.com/AKSW/OntoWiki/wiki/VirtuosoBackend#setting-up-odbc
  for more details.
  * sparql.user: Your administrator username for virtuoso.
  * sparql.password: Your administrator password for virtuoso.
  * isql.bin = The full path of your isql (or isql-vt) binary.
This phing target will give the SPARQL user, the update permission.
* import-rdf-fixtures: The same variables as setup-virtuoso-permissions need to
be set to your build.properties.local file for this to work.
This will import rdf files located in the
`[project root directory]/resources/fixtures` directory. In order to let
virtuoso accept importing files from this directory, you have to append this
directory to the configuration file of your virtuoso. Locate and open the
virtuoso configuration file and search for the `DirsAllowed` key under the
`[Parameters]` section and append the full path of the fixtures directory.
Restart your virtuoso server and then you can import the rdf files.