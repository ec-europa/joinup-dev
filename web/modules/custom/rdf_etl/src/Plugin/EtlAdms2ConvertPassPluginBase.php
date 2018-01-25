<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Plugin;

use Drupal\Core\Database\Database;
use Drupal\Core\Plugin\PluginBase;
use Drupal\KernelTests\KernelTestBase;

/**
 * Provides a base class for Adms2ConvertPass plugins.
 */
abstract class EtlAdms2ConvertPassPluginBase extends PluginBase implements EtlAdms2ConvertPassInterface {

  /**
   * The SPARQL database connection.
   *
   * @var \Drupal\rdf_entity\Database\Driver\sparql\Connection
   */
  protected $sparql;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->sparql = Database::getConnection('default', 'sparql_default');
  }

  /**
   * {@inheritdoc}
   */
  public function convert(array $data): void {}

  /**
   * {@inheritdoc}
   */
  public function performAssertions(KernelTestBase $test): void {}

  /**
   * {@inheritdoc}
   */
  public function getTestingRdfData(): ?string {
    return NULL;
  }

  /**
   * Gets all the triples from a given graph, ordered by subject.
   *
   * @param string $graph_uri
   *   The graph URI.
   * @param string|null $subject
   *   (optional) If passed, the results will be limited to this subject.
   *
   * @return array[]
   *   The query results as array.
   */
  protected function getTriplesFromGraph(string $graph_uri, ?string $subject = NULL): array {
    $filter = $subject ? "VALUES ?subject { <$subject> } ." : '';
    $query = <<<QUERY
SELECT ?graph ?subject ?predicate ?object
FROM NAMED <$graph_uri>
WHERE {
  GRAPH ?graph {
    ?subject ?predicate ?object .
    $filter
  }
}
ORDER BY ?subject
QUERY;

    $return = [];
    /** @var \EasyRdf\Sparql\Result $results */
    $results = $this->sparql->query($query);
    foreach ($results as $result) {
      $return[] = [
        'subject' => (string) $result->subject,
        'predicate' => (string) $result->predicate,
        'object' => (string) $result->object,
      ];
    }

    return $return;
  }

  /**
   * Deletes a set of triples having the same graph, subject and predicate.
   *
   * @param string $graph_uri
   *   The graph URI.
   * @param string $subject
   *   The subject.
   * @param string $predicate
   *   The predicate.
   * @param string[]|null $objects
   *   (optional) If passed, restrict the deletion to this set of objects.
   */
  protected function deleteTriples(string $graph_uri, string $subject, string $predicate, ?array $objects = NULL): void {
    if ($objects) {
      $values = array_map(function (string $object) use ($subject, $predicate): string {
        return "<$subject> <$predicate> $object .";
      }, $objects);
      $values = implode("\n", $values);
    }
    else {
      $values = "<$subject> <$predicate> ?object .";
    }
    $this->sparql->query("DELETE DATA FROM <$graph_uri> { $values }");
  }

}
