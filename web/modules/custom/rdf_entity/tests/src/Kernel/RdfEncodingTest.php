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
    $path = DRUPAL_ROOT . "/../vendor/minimaxir/big-list-of-naughty-strings/blns.json";
    if (!file_exists($path)) {
      $this->markTestSkipped('Library minimaxir/big-list-of-naughty-strings is required.');
      return;
    }
    $json = file_get_contents($path);
    $naughty_strings = json_decode($json);
    foreach ($naughty_strings as $naughty_string) {
      // Ignore the empty string test, as the field won't be set.
      if ($naughty_string === "") {
        continue;
      }
      $rdf = Rdf::create([
        'rid' => 'dummy',
        'label' => 'naughty object',
        'field_text' => $naughty_string,
      ]);
      try {
        $rdf->save();
      }
      catch (\Exception $e) {
        fwrite(STDOUT, $e->getMessage() . "\n");
        fwrite(STDOUT, $e->getTraceAsString() . "\n");
        $msg = sprintf("Entity saved for naughty string '%s'.", $naughty_string);
        $this->assertTrue(FALSE, $msg);
      }

      $query = \Drupal::entityQuery('rdf_entity')
        ->condition('label', 'naughty object')
        ->condition('rid', 'dummy')
        ->range(0, 1);

      $result = $query->execute();
      $msg = sprintf("Loaded naughty object '%s'.", $naughty_string);
      $this->assertFalse(empty($result), $msg);

      $loaded_rdf = NULL;
      try {
        $loaded_rdf = Rdf::load(reset($result));
      }
      catch (\Exception $e) {
        fwrite(STDOUT, $e->getMessage() . "\n");
        fwrite(STDOUT, $e->getTraceAsString() . "\n");
        $msg = sprintf("Entity loaded for naughty string '%s'.", $naughty_string);
        $this->assertTrue(FALSE, $msg);
      }

      $field = $loaded_rdf->get('field_text');
      $msg = sprintf("Field was empty for naughty string '%s'.", $naughty_string);
      $this->assertTrue($field, $msg);
      $first = $field->first();
      $msg = sprintf("First value set for naughty string '%s'.", $naughty_string);
      $this->assertTrue($first, $msg);
      $text = $first->getValue();

      $msg = sprintf("Naughty string '%s' was correctly read back.", $naughty_string);
      $this->assertEquals($text['value'], $naughty_string, $msg);
      $rdf->delete();
    }

  }

}
