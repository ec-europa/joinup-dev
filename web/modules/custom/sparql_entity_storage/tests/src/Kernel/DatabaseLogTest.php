<?php

declare(strict_types = 1);

namespace Drupal\Tests\sparql_entity_storage\Kernel;

use Drupal\Core\Database\Database;

/**
 * Tests the query logging.
 *
 * @group sparql_entity_storage
 *
 * @coversDefaultClass \Drupal\sparql_entity_storage\Driver\Database\sparql\Connection
 */
class DatabaseLogTest extends SparqlKernelTestBase {

  /**
   * Tests the log.
   *
   * @param string $method
   *   The method name 'query' or 'update'.
   * @param string $query
   *   The query.
   * @param array $args
   *   The query arguments.
   * @param string|null $expected_exception_message
   *   The expected exception message, if any.
   *
   * @dataProvider provider
   */
  public function testLog(string $method, string $query, array $args, ?string $expected_exception_message): void {
    if ($expected_exception_message) {
      $this->expectException(\InvalidArgumentException::class);
      $this->expectExceptionMessage($expected_exception_message);
    }

    Database::startLog('log_test', 'sparql_default');
    $this->sparql->{$method}($query, $args);
    $log = $this->sparql->getLogger()->get('log_test');

    $this->assertCount(1, $log);

    $log_entry = reset($log);
    $this->assertEquals($query, $log_entry['query']);
    $this->assertSame($args, $log_entry['args']);
    $this->assertEquals('default', $log_entry['target']);
    $this->assertEquals('double', gettype($log_entry['time']));
    $this->assertGreaterThan(0, $log_entry['time']);
    // @todo Inspect also $log_entry['caller'] when
    // https://www.drupal.org/project/drupal/issues/2867788 lands.
    // @see https://www.drupal.org/project/drupal/issues/2867788
  }

  /**
   * Data provider for ::testLog().
   *
   * @return array
   *   Test cases.
   *
   * @see DatabaseLogTest::testLog()
   */
  public function provider(): array {
    return [
      'query' => [
        'query',
        'SELECT DISTINCT ?s ?p ?o WHERE { ?s ?p ?o } LIMIT 100',
        [],
        NULL,
      ],
      'update' => [
        'update',
        'CLEAR GRAPH <http://example.com>;',
        [],
        NULL,
      ],
      'query with arguments' => [
        'query',
        'SELECT DISTINCT ?s ?p ?o WHERE { <:subject> ?p ?o } LIMIT 100',
        [':subject' => 'http://example.com'],
        'Replacement arguments are not yet supported.',
      ],
    ];
  }

}
