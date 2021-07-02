<?php

declare(strict_types = 1);

namespace Drupal\joinup_sparql;

use Drupal\Core\Database\Database;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use EasyRdf\Graph;
use EasyRdf\GraphStore;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * JLA service modify vocabulary fixtures.
 */
class VocabularyFixturesReplace implements ContainerInjectionInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
  }

  /**
   * Replace current JLA list.
   */
  public function updatejla() {
    $sparql_connection = Database::getConnection('default', 'sparql_default');

    // Re import the file to update the terms.
    $connection_options = $sparql_connection->getConnectionOptions();
    $connect_string = "http://{$connection_options['host']}:{$connection_options['port']}/sparql-graph-crud";
    $graph_store = new GraphStore($connect_string);

    $filepath = __DIR__ . '/../../../../../resources/fixtures/licence-legal-type.rdf';
    $graph = new Graph('http://licence-legal-type');
    $graph->parse(file_get_contents($filepath));
    $graph_store->replace($graph, 'http://licence-legal-type');
  }

}
