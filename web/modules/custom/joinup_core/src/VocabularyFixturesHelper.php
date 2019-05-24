<?php

declare(strict_types = 1);

namespace Drupal\joinup_core;

use Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface;
use EasyRdf\Graph;
use EasyRdf\GraphStore;

/**
 * Contains helper methods to handle and import vocabularies from fixtures.
 */
class VocabularyFixturesHelper implements VocabularyFixturesHelperInterface {

  /**
   * The SPARQL connection service.
   *
   * @var \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface
   */
  protected $connection;

  /**
   * Constructs a VocabularyFixturesHelper.
   *
   * @param \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface $connection
   *   The SPARQL connection service.
   */
  public function __construct(ConnectionInterface $connection) {
    $this->connection = $connection;
  }

  /**
   * Returns an array of allowed values and data related to the imports.
   *
   * @return array
   *   An array of allowed values where each entry is a an array containing the
   *   following data:
   *   - graph: The graph uri of the vocabulary.
   *   - filename: The filename of the vocabulary.
   *   - extra queries: Extra queries that should run after each import of the
   *   file.
   */
  protected function getFixturesData(): array {
    return [
      'licence-legal-type' => [
        'graph' => 'http://licence-legal-type',
        'filename' => 'licence-legal-type.rdf',
        'extra queries' => [
          'WITH <http://licence-legal-type> INSERT { ?subject a skos:Concept } WHERE { ?subject a skos:Collection . };',
          'WITH <http://licence-legal-type> INSERT { ?subject skos:topConceptOf <http://joinup.eu/legal-type#> } WHERE { ?subject a skos:Concept . FILTER NOT EXISTS { ?subject skos:topConceptOf <http://joinup.eu/legal-type#> } };',
          'WITH <http://licence-legal-type> INSERT { ?member skos:broaderTransitive ?collection } WHERE { ?collection a skos:Collection . ?collection skos:member ?member };',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function clearFixturesGraph(string $graph_uri): void {
    $this->connection->getSparqlClient()->clear($graph_uri);
  }

  /**
   * {@inheritdoc}
   */
  public function importFixtures(string $fixture_key, bool $clear_graph = TRUE): void {
    $fixtures_data = $this->getFixturesData();
    if (!isset($fixtures_data[$fixture_key])) {
      throw new \Exception("Invalid option '{$fixture_key}' supply'.");
    }

    if ($clear_graph) {
      $this->clearFixturesGraph($fixtures_data[$fixture_key]['graph']);
    }

    $graph = new Graph($fixtures_data[$fixture_key]['graph']);
    $filename = DRUPAL_ROOT . '/../resources/fixtures/' . $fixtures_data[$fixture_key]['filename'];
    $graph->parseFile($filename);

    $connection_options = $this->connection->getConnectionOptions();
    $connect_string = "http://{$connection_options['host']}:{$connection_options['port']}/sparql-graph-crud";
    $graph_store = new GraphStore($connect_string);
    $graph_store->insert($graph);

    foreach ($fixtures_data[$fixture_key]['extra queries'] as $query) {
      $this->connection->query($query);
    }
  }

}
