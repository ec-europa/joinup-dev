Database connection:

Add to your settings.php file:
$databases['sparql_default']['sparql'] = array (
  'prefix' => '',
  'host' => '127.0.0.1',
  'port' => '8890',
  'namespace' => 'Drupal\\rdf_entity\\Database\\Driver\\sparql',
  'driver' => 'sparql',
);