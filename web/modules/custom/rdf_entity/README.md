# Getting started
A working Sparql endpoint is needed to use the rdf entity module.
You could either use a remote Sparql endpoint, or you could set one up locally.

Virtuoso is one of the more robust triple store solutions available, but any solution would do.

    @todo Create an example module that uses [http://dbpedia.org/sparql](http://dbpedia.org/sparql)

## Setting up Virtuoso
On a debian based system:
 apt-get install virtuoso-opensource
 service virtuoso-opensource-6.1 start
 (Set the password during installation)

 Go to [http://localhost:8890/conductor/](http://localhost:8890/conductor/)
 and login in with: dba - yourpass

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

