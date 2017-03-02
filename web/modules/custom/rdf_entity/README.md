# Getting started
A working Sparql endpoint is needed to use the rdf entity module.
You could either use a remote Sparql endpoint, or you could set one up locally.

Virtuoso is one of the more robust triple store solutions available, but any solution would do.

    @todo Create an example module that uses [http://dbpedia.org/sparql](http://dbpedia.org/sparql)

## Setting up Virtuoso
### On a Debian based system
 apt-get install virtuoso-opensource
 service virtuoso-opensource-6.1 start
 (Set the password during installation)

### On Mac OS X system
- Install Homebrew (see http://brew.sh)
- `$ brew install virtuoso`
- Start Virtuoso
  ```
  # The version might be differnet than 7.2.4.2.
  $ cd /usr/local/Cellar/virtuoso/7.2.4.2/var/lib/virtuoso/db
  $ virtuoso-t -f &
  ```
- Administer at [http://localhost:8890/conductor/](http://localhost:8890/conductor/). Login with dba/dba.

### On an Arch Linux based system
- Install the [Virtuoso AUR package](https://aur.archlinux.org/packages/virtuoso/).
- `# systemctl start virtuoso`

 Go to [http://localhost:8890/conductor/](http://localhost:8890/conductor/)
 and login in with: dba - yourpass

Grant 'update' rights to the SPARQL user:
System admin -> Users -> SPARQL (edit)
Account roles -> Put SPARQL_UPDATE in 'Selected'

## Connecting Drupal to the Sparql endpoint
The following example demonstrates the use with a local Virtuoso installation.
To connect Drupal to the endpoint, the db connection should be added to the settings.php file.

    $databases['sparql_default']['sparql'] = array (
      'prefix' => '',
      'host' => '127.0.0.1',
      'port' => '8890',
      'namespace' => 'Drupal\\rdf_entity\\Database\\Driver\\sparql',
      'driver' => 'sparql',
    );
