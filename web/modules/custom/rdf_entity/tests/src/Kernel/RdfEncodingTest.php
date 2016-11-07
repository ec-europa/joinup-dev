<?php

namespace Drupal\Tests\rdf_entity\Kernel;

use Drupal\Core\Database\Database;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the support of saving various encoded stings in the triple store.
 *
 * @group rdf_entity
 */
class RdfEncodingTest extends KernelTestBase {

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = array(
    'rdf_entity',
    'rdf_entity_test'
  );

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    if (!$this->detectSparqlAvailability()) {
      $this->markTestSkipped('No Sparql connection available.');
      print "Connected\n";
    }
    print "Setup\n";

    $this->installConfig(['rdf_entity', 'rdf_entity_test']);
    print "Installed\n";
    $this->installEntitySchema('rdf_entity');
    print "schema\n";

    // $this->detectSolrAvailability();
  }

  protected function detectSparqlAvailability() {
    // If the test is run with argument dburl then use it.
    $db_url = getenv('SIMPLETEST_SPARQL_DB');
    var_dump($db_url);
    if (!empty($db_url)) {
      $database = Database::convertDbUrlToConnectionInfo($db_url, DRUPAL_ROOT);
      $database['namespace'] = 'Drupal\\rdf_entity\\Database\\Driver\\sparql';
      Database::addConnectionInfo('sparql_default', 'default', $database);
      var_dump($database);
    }
    
    return TRUE;
  }




  /**
   * Clear the index after every test.
   */
  public function tearDown() {
    parent::tearDown();
  }

  function testEncoding() {
    $this->assertEquals(TRUE, TRUE, 'jej');
  }
}
