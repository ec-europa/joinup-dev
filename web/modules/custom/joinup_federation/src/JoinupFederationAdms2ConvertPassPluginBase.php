<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation;

use Drupal\Core\Database\Database;
use Drupal\Core\Plugin\PluginBase;
use Drupal\KernelTests\KernelTestBase;

/**
 * Provides a base class for Adms2ConvertPass plugins.
 */
abstract class JoinupFederationAdms2ConvertPassPluginBase extends PluginBase implements JoinupFederationAdms2ConvertPassInterface {

  /**
   * The SPARQL database connection.
   *
   * @var \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface
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
   * @param string|null $predicate
   *   (optional) If passed, the results will be limited to this predicate.
   * @param string|null $objects
   *   (optional) A serialized list of objects (delimited by space). If passed,
   *   the results will be limited to this list objects.
   *
   * @return array[]
   *   The query results as array.
   */
  protected function getTriplesFromGraph(string $graph_uri, ?string $subject = NULL, ?string $predicate = NULL, ?string $objects = NULL): array {
    $filter = [];
    $filter[] = $subject ? "VALUES ?subject { <$subject> }" : NULL;
    $filter[] = $predicate ? "VALUES ?predicate { <$predicate> }" : NULL;
    $filter[] = $objects ? "VALUES ?object { $objects }" : NULL;
    $filter = implode(" .\n", array_filter($filter));

    $limit = 10000;

    // Using a sub-query to prevent Virtuoso 22023 Error SR353.
    // @see http://vos.openlinksw.com/owiki/wiki/VOS/VirtTipsAndTricksHowToHandleBandwidthLimitExceed
    $query = <<<QUERY
SELECT ?graph ?subject ?predicate ?object
WHERE {
  SELECT ?graph ?subject ?predicate ?object
  FROM NAMED <{$graph_uri}>
  WHERE {
    GRAPH ?graph {
      ?subject ?predicate ?object .
      {$filter}
    }
  }
  ORDER BY ?subject
}
LIMIT <{$limit}>
OFFSET %d
QUERY;

    $offset = 0;
    $return = [];

    // Split the results in chunks to overcome the virtuoso.ini ResultSetMaxRows
    // value which usually defaults to 1000000.
    // @see https://virtuoso.openlinksw.com/dataspace/doc/dav/wiki/Main/VirtConfigScale#Configuration%20Options
    do {
      /** @var \EasyRdf\Sparql\Result $results */
      $results = $this->sparql->query(sprintf($query, $offset));
      foreach ($results as $result) {
        $return[] = [
          'subject' => (string) $result->subject,
          'predicate' => (string) $result->predicate,
          'object' => (string) $result->object,
        ];
      }
      $offset += $limit;
    } while ($results->count());

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
      $this->sparql->query("DELETE DATA FROM <$graph_uri> { $values }");
    }
    else {
      $query = <<<QUERY
DELETE FROM <$graph_uri> {
  ?subject ?predicate ?object .
}
WHERE {
 ?subject ?predicate ?object .
 FILTER(?subject = <$subject>) .
 FILTER(?predicate = <$predicate>) .
}
QUERY;
      $this->sparql->update($query);
    }
  }

  /**
   * Inserts triples in the SPARQL data store, in a given graph.
   *
   * Passed array triples expects the following structure:
   * @code
   * [
   *   'subject1' = [
   *     'predicate1' => [
   *       'object1',
   *       'object2',
   *     ],
   *     'predicate2' => [
   *        ...
   *     ]
   *   ],
   *   'subject2' => [
   *      ...
   *   ],
   * ]
   * @endcode
   * All the array elements (keys and values) are already serialized.
   *
   * @param string $graph_uri
   *   The graph URI.
   * @param array $triples
   *   Triples array.
   *
   * @todo Consider using \EasyRdf\Graph.
   */
  protected function insertTriples(string $graph_uri, array $triples):void {
    $expanded_triples = [];
    foreach ($triples as $subject => $predicates) {
      foreach ($predicates as $predicate => $objects) {
        foreach ($objects as $object) {
          $expanded_triples[] = "$subject $predicate $object .";
        }
      }
    }
    if ($expanded_triples) {
      $values = implode("\n", $expanded_triples);
      $this->sparql->update("INSERT DATA INTO <$graph_uri> { $values }");
    }
  }

  /**
   * Iterates over all triples from a graph and calls a callback on them.
   *
   * Creates simple representations of each entities within a graph given a
   * predicate value and passes each of this structures to callback that is
   * responsible to do the actual conversion. For example, next triples:
   * @code
   * <http://example.com/1> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://example.com/type/1>
   * <http://example.com/1> <http://example.com/pred/1> <http://example.com/obj/1>
   * <http://example.com/1> <http://example.com/pred/1> <http://example.com/obj/2>
   * <http://example.com/1> <http://example.com/pred/1> <http://example.com/obj/3>
   * <http://example.com/1> <http://example.com/pred/2> <http://example.com/obj/4>
   * <http://example.com/1> <http://example.com/pred/2> <http://example.com/obj/5>
   * <http://example.com/2> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://example.com/type/2>
   * <http://example.com/2> <http://example.com/pred/1> <http://example.com/obj/6>
   * <http://example.com/2> <http://example.com/pred/2> <http://example.com/obj/7>
   * @endcode
   * with the predicate value 'http://example.com/pred/1' will create next
   * entity structures and will pass each to a given callback for processing:
   * @codingStandardsIgnoreStart
   * @code
   * [
   *   'type' => 'http://example.com/type/1',
   *   'http://example.com/pred/1'  => [
   *      'http://example.com/obj/1',
   *      'http://example.com/obj/2',
   *      'http://example.com/obj/3',
   *    ],
   * ],
   * [
   *   'type' => 'http://example.com/type/2',
   *   'http://example.com/pred/1'  => [
   *      'http://example.com/obj/6',
   *    ],
   * ]
   * @endcode
   * @codingStandardsIgnoreEnd
   *
   * @param string $graph_uri
   *   The graph URI.
   * @param string $predicate_value
   *   The predicate value.
   */
  protected function processGraph(string $graph_uri, string $predicate_value): void {
    $entity = [];
    $last_subject = NULL;
    $results = $this->getTriplesFromGraph($graph_uri);
    foreach ($results as $delta => $result) {
      // Cursor moved to a new set of triples?
      if ($result['subject'] !== $last_subject) {
        $this->processGraphCallback($graph_uri, $last_subject, $predicate_value, $entity);
        $entity = [];
      }

      if ($result['predicate'] === 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type') {
        $entity['type'] = $result['object'];
      }
      if ($result['predicate'] === $predicate_value) {
        $entity[$predicate_value][] = $result['object'];
      }

      // Store the last subject so we can check on the next iteration.
      $last_subject = $result['subject'];

      // Just before finishing, call the callback method for the last entity.
      if ($delta === count($results) - 1) {
        $this->processGraphCallback($graph_uri, $result['subject'], $predicate_value, $entity);
      }
    }
  }

  /**
   * Performs the conversion for entities extracted in ::processGraph().
   *
   * @param string $graph
   *   The graph URI.
   * @param string|null $subject
   *   The subject of the entity set.
   * @param string $predicate
   *   The predicate.
   * @param array $entity
   *   A simple representation of an entity. See ::processGraph().
   *
   * @see \Drupal\pipeline\JoinupFederationAdms2ConvertPassPluginBase::processGraph()
   */
  protected function processGraphCallback(string $graph, ?string $subject, string $predicate, array $entity): void {}

}
