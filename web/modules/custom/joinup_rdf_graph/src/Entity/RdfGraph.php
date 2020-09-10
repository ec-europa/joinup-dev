<?php

declare(strict_types = 1);

namespace Drupal\joinup_rdf_graph\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\sparql_entity_storage\SparqlGraphStoreTrait;
use EasyRdf\Graph;

/**
 * Entity class for 'rdf_graph' RDF entities.
 */
class RdfGraph extends Rdf implements RdfGraphInterface {

  use SparqlGraphStoreTrait;

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities): void {
    parent::postDelete($storage, $entities);

    $graph_store = static::createGraphStore();
    /** @var \Drupal\joinup_rdf_graph\Entity\RdfGraphInterface $rdf_graph */
    foreach ($entities as $rdf_graph) {
      // Delete the triples from this graph.
      $graph_store->delete($rdf_graph->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE): void {
    parent::postSave($storage, $update);

    // Replace or insert the triples.
    $rdf_file_path = $this->get('field_rdf_file')->entity->getFileUri();
    $graph = new Graph($this->id());
    $graph->parseFile($rdf_file_path);
    $this->createGraphStore()->replace($graph);
  }

}
