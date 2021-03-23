# Description

The __SPARQL Entity Storage__ module offers a
[SPARQL](https://en.wikipedia.org/wiki/SPARQL) backend for Drupal entities. The
module provides an entity storage and query. Simply enabling the module, doesn't
bring any user visible new feature, the module is a prerequisite for other
modules that are defining Drupal entities that requires a SPARQL triple-store
backend. A very simple example of such an entity is the `sparql_test` entity
provided in the __SPARQL Test__ testing module, included in this package (see
the [SparqlTest](./tests/modules/sparql_test/src/Entity/SparqlTest.php) class).
A more sophisticated module that uses the SPARQL backend, and runs already in
the wild, is the [RDF Entity](https://www.drupal.org/project/rdf_entity) module.
The entity provided there (`rdf_entity`) can be used as it is, can be extended
or, simply, used as an example when designing a new entity type.

# Getting started

A working SPARQL endpoint is needed. You could either use a remote SPARQL
endpoint, or you could set one up locally. Virtuoso is one of the more robust
triple store solutions available, but any solution would do.

@todo Create an example module that uses
[http://dbpedia.org/sparql](http://dbpedia.org/sparql)

## Setting up Virtuoso

### On a Debian based system

`apt-cache search "^virtuoso"` will show you available packages.

```
$ apt-get install virtuoso-opensource
$ service virtuoso-opensource-6.1 start
```
 
(Set the password during installation)

### On a MacOS system

- Install Homebrew (see http://brew.sh)
- `$ brew install virtuoso`
- Start Virtuoso
    ```
    # The version might be differnet than 7.2.4.2.
    $ cd /usr/local/Cellar/virtuoso/7.2.4.2/var/lib/virtuoso/db
    $ virtuoso-t -f &
    ```
- Administer at
[http://localhost:8890/conductor/](http://localhost:8890/conductor/). Login with dba/dba.

### On an Arch Linux based system

- Install the
  [Virtuoso AUR package](https://aur.archlinux.org/packages/virtuoso/).
- `# systemctl start virtuoso`

Go to
[http://localhost:8890/conductor/](http://localhost:8890/conductor/)
and login in with: dba - yourpass.

- Grant 'update' rights to the SPARQL user:
- System admin -> Users -> SPARQL (edit)
- Account roles -> Put SPARQL_UPDATE in 'Selected'

## Connecting Drupal to the SPARQL endpoint

The following example demonstrates the use with a local Virtuoso installation. To connect Drupal to the endpoint, the db connection should be added to the settings.php file.

```php
$databases['sparql_default']['sparql'] = [
  'prefix' => '',
  'host' => '127.0.0.1',
  'port' => '8890',
  'namespace' => 'Drupal\\Driver\\Database\\sparql',
  'driver' => 'sparql',
  // Optional. This is actually the endpoint path. If omitted, 'sparql' will
  // be used.
  'database' => 'data/endpoint',
  // If the connection to the endpoint should be HTTPS secured. If omitted,
  // FALSE is assumed.
  'https' => FALSE,
];
```

## Content translation
Entities using SPARQL storage support basic content translations. This is still WIP.

_Note:_ If content translations are enabled, the 'langcode' property _must_ be mapped, otherwise entity reference fields will not store information.
