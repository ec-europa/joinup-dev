<?php

declare(strict_types = 1);

namespace Drupal\Tests\sparql_entity_storage\Kernel;

use Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlArg;
use Drupal\sparql_test\Entity\SparqlTest;

/**
 * Tests date/time.
 *
 * @group sparql_entity_storage
 */
class DateTimeFieldsTest extends SparqlKernelTestBase {

  /**
   * Tests handling of timestamp field properties.
   */
  public function testTimestampFieldProperties() {
    // Drupal tests explicitly set the time zone to Australia/Sydney.
    // @see web/core/tests/bootstrap.php:157
    // @see web/core/lib/Drupal/Core/Test/FunctionalTestSetupTrait.php:348
    $created_iso = '2017-09-01T18:09:22+10:00';
    $changed_iso = '2017-09-02T18:09:22+10:00';
    $created_unix = strtotime($created_iso);
    $changed_unix = strtotime($changed_iso);

    $entity = SparqlTest::create([
      'type' => 'fruit',
      'id' => 'http://example.com',
      'label' => $this->randomMachineName(),
      'created' => $created_unix,
      'changed' => $changed_unix,
    ]);
    $entity->save();

    $loaded = $this->container->get('entity_type.manager')->getStorage('sparql_test')->loadUnchanged($entity->id());

    $this->assertTripleDataType($loaded->id(), 'http://purl.org/dc/terms/issued', 'http://www.w3.org/2001/XMLSchema#dateTime');
    $this->assertTripleDataType($loaded->id(), 'http://example.com/modified', 'http://www.w3.org/2001/XMLSchema#integer');

    // Verify that the retrieved values are presented as timestamp.
    $this->assertEquals($created_unix, $loaded->get('created')->value);
    $this->assertEquals($changed_unix, $loaded->get('changed')->value);

    // Assert that timestamp properties mapped as integer are stored as such.
    $this->assertTripleValue($loaded->id(), 'http://example.com/modified', $changed_unix);
    // Assert the stored value of timestamps mapped as xsd:dateTime.
    $this->assertTripleValue($loaded->id(), 'http://purl.org/dc/terms/issued', $created_iso);
  }

  /**
   * Asserts the data type of a triple.
   *
   * @param string $subject
   *   The triple subject.
   * @param string $predicate
   *   The triple predicate.
   * @param string $object_data_type
   *   The expected triple object data type.
   */
  protected function assertTripleDataType($subject, $predicate, $object_data_type) {
    $subject = SparqlArg::uri($subject);
    $predicate = SparqlArg::uri($predicate);
    $object_data_type = SparqlArg::uri($object_data_type);

    $query = <<<AskQuery
ASK WHERE {
  $subject $predicate ?o .
  filter (datatype(?o) = $object_data_type)
}
AskQuery;

    $this->assertTrue($this->sparql->query($query)->getBoolean(), "Incorrect data type '$object_data_type' for predicate '$predicate'.");
  }

  /**
   * Asserts the stored value of a triple.
   *
   * @param string $subject
   *   The triple subject.
   * @param string $predicate
   *   The triple predicate.
   * @param string $expected_value
   *   The expected triple value.
   */
  protected function assertTripleValue($subject, $predicate, $expected_value) {
    $subject = SparqlArg::uri($subject);
    $predicate = SparqlArg::uri($predicate);

    $query = <<<SelectQuery
SELECT ?object WHERE {
  $subject $predicate ?object
}
SelectQuery;

    $result = $this->sparql->query($query);
    $this->assertCount(1, $result, 'Expected a single result, but got ' . $result->count());
    $this->assertEquals($expected_value, (string) $result[0]->object);
  }

}
