<?php

namespace Drupal\Tests\rdf_entity\Kernel;

use Drupal\Core\Database\Database;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Tests the support of saving various encoded stings in the triple store.
 *
 * @group rdf_entity
 */
class RdfEncodingTest extends EntityKernelTestBase {

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = array(
    'rdf_entity',
    'rdf_entity_test',
    'field',
    'node',
    'system',
    'options',
    'entity_reference',
  );

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    if (!$this->setUpSparql()) {
      $this->markTestSkipped('No Sparql connection available.');
    }

    $this->installConfig(['rdf_entity']);
    $this->installEntitySchema('rdf_entity');

    $this->installConfig(['rdf_entity_test']);

    // $this->detectSolrAvailability();
  }

  /**
   * Setup the db connection to the triple store.
   */
  protected function setUpSparql() {
    // If the test is run with argument db url then use it.
    // export SIMPLETEST_SPARQL_DB='sparql://127.0.0.1:8890/'.
    $db_url = getenv('SIMPLETEST_SPARQL_DB');
    if (empty($db_url)) {
      return FALSE;
    }
    $database = Database::convertDbUrlToConnectionInfo($db_url, DRUPAL_ROOT);
    $database['namespace'] = 'Drupal\\rdf_entity\\Database\\Driver\\sparql';
    Database::addConnectionInfo('sparql_default', 'default', $database);

    return TRUE;
  }

  /**
   * Clear the index after every test.
   */
  public function tearDown() {
    parent::tearDown();
  }

  /**
   * Test that naughty strings can safely be saved to the database.
   */
  public function testEncoding() {
    $rdf = Rdf::create([
      'rid' => 'dummy',
      'label' => 'jaa',
    ]);
    $rdf->save();

    $label = $rdf->get('label')->first()->getValue();
    $this->assertEquals($label['value'], 'jaa', 'Labels are equal');
    $rdf->delete();
  }

}
