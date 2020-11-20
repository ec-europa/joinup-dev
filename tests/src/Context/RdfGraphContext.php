<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\Gherkin\Node\TableNode;
use Drupal\Component\Utility\SortArray;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\joinup\Traits\RdfEntityTrait;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Behat context for RDF graph testing.
 */
class RdfGraphContext extends RawDrupalContext {

  use RdfEntityTrait;

  /**
   * A list of graphs used during testing.
   *
   * @var string[]
   */
  protected $usedRdfGraphs = [];

  /**
   * Asserts that a list of triples exist in 'rdf_graph' entity graph.
   *
   * Table format:
   *
   * @codingStandardsIgnoreStart
   * | subject               | predicate             | object                |
   * | http://example.com/s1 | http://example.com/p1 | http://example.com/o1 |
   * | http://example.com/s2 | http://example.com/p2 | http://example.com/o2 |
   * @codingStandardsIgnoreEnd
   *
   * @param string $rdf_graph_label
   *   The RDF graph entity label.
   * @param \Behat\Gherkin\Node\TableNode $triples
   *   The expected list of triples.
   *
   * @Then the :rdf_graph_label RDF graph contains triples:
   */
  public function assertRdfGraphTriples(string $rdf_graph_label, TableNode $triples): void {
    $rdf_graph = $this->getRdfEntityByLabel($rdf_graph_label, 'rdf_graph');

    $keys = ['subject', 'predicate', 'object'];

    // Get and normalize the actual triples.
    $query = "SELECT ?subject ?predicate ?object FROM <{$rdf_graph->id()}> WHERE { ?subject ?predicate ?object } ORDER BY ?subject ?predicate ?object";
    $results = \Drupal::service('sparql.endpoint')->query($query);
    $actual_triples = [];
    /** @var \EasyRdf\Sparql\Result $results */
    foreach ($results as $result) {
      $triple = [];
      foreach ($keys as $key) {
        $triple[$key] = (string) $result->{$key};
      }
      $actual_triples[] = $triple;
    }

    // Normalize the expected triples array.
    $expected_triples = $triples->getColumnsHash();
    foreach (['object', 'predicate', 'subject'] as $key) {
      uasort($expected_triples, function (array $a, array $b) use ($key): int {
        return SortArray::sortByKeyString($a, $b, $key);
      });
    }
    $expected_triples = array_values($expected_triples);

    if ($actual_triples !== $expected_triples) {
      throw new ExpectationFailedException("Triples from <{$rdf_graph_label}> RDF graph are not matching the expected values.");
    }

    // Register this graph as used during the test.
    $this->usedRdfGraphs[$rdf_graph->id()] = $rdf_graph_label;
  }

  /**
   * Asserts that the triples created during test were deleted.
   *
   * @Then triples created during the test were deleted
   */
  public function assertNoRdfGraphTriples(): void {
    $from = [];
    foreach (array_keys($this->usedRdfGraphs) as $uri) {
      $from[] = "FROM <{$uri}>";
    }
    $from = implode(" ", $from);
    $query = "SELECT ?subject ?predicate ?object {$from} WHERE { ?subject ?predicate ?object }";

    /** @var \EasyRdf\Sparql\Result $results */
    $results = \Drupal::service('sparql.endpoint')->query($query);
    if ($count = $results->count()) {
      throw new ExpectationFailedException("The graphs created during tests contain {$count} triples but they should be empty.");
    }
  }

}
