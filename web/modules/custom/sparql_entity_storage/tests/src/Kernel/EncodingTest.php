<?php

declare(strict_types = 1);

namespace Drupal\Tests\sparql_entity_storage\Kernel;

use Drupal\sparql_test\Entity\SparqlTest;

/**
 * Tests the support of saving various encoded stings in the triple store.
 *
 * @group sparql_entity_storage
 */
class EncodingTest extends SparqlKernelTestBase {

  /**
   * Test that naughty strings can safely be saved to the database.
   */
  public function testEncoding(): void {
    $path = DRUPAL_ROOT . "/../vendor/minimaxir/big-list-of-naughty-strings/blns.json";
    if (!file_exists($path)) {
      // Retry with the vendor directory in the Drupal root.
      $path = DRUPAL_ROOT . "/vendor/minimaxir/big-list-of-naughty-strings/blns.json";
      if (!file_exists($path)) {
        // Retry with the module's vendor directory.
        $path = __DIR__ . "/../../../vendor/minimaxir/big-list-of-naughty-strings/blns.json";
        if (!file_exists($path)) {
          $this->markTestSkipped('Library minimaxir/big-list-of-naughty-strings is required.');
          return;
        }
      }
    }
    $json = file_get_contents($path);
    $naughty_strings = json_decode($json);
    foreach ($naughty_strings as $naughty_string) {
      // Ignore the empty string test, as the field won't be set.
      if ($naughty_string === "") {
        continue;
      }
      $rdf = SparqlTest::create([
        'type' => 'fruit',
        'title' => 'Berry',
        'text' => $naughty_string,
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

      $result = $this->container->get('entity_type.manager')
        ->getStorage('sparql_test')
        ->getQuery()
        ->condition('title', 'Berry')
        ->condition('type', 'fruit')
        ->range(0, 1)
        ->execute();
      $this->assertNotEmpty($result);

      $loaded_rdf = NULL;
      try {
        $loaded_rdf = SparqlTest::load(reset($result));
      }
      catch (\Exception $e) {
        fwrite(STDOUT, $e->getMessage() . "\n");
        fwrite(STDOUT, $e->getTraceAsString() . "\n");
        $this->assertTrue(FALSE);
      }

      $field = $loaded_rdf->get('text');
      $this->assertNotEmpty($field);
      $first = $field->first();
      $this->assertNotEmpty($first);
      $text = $first->getValue();

      $this->assertSame($naughty_string, $text['value']);
      $rdf->delete();
    }
  }

}
