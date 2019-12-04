<?php

declare(strict_types = 1);

namespace Drupal\solution;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface;
use Drupal\sparql_entity_storage\SparqlEntityStorageGraphHandlerInterface;
use Drupal\sparql_entity_storage\SparqlEntityStorageInterface;

/**
 * Defines a field item list class for the solution 'collections' field.
 *
 * In ADMS-AP collections point to solutions. The reverse relation would have
 * been more logical, and this is quite inconvenient, especially for the search
 * index. This field computes the reverse relationship but is a read-write
 * field, allowing also to set the parent collection.
 */
class SolutionAffiliationFieldItemList extends EntityReferenceFieldItemList {

  use ComputedItemListTrait;

  /**
   * If the current solution is in one of the 'official' graphs.
   *
   * An 'official graph' is one of the graphs returned by
   * SparqlEntityStorageGraphHandlerInterface::getEntityTypeDefaultGraphIds().
   *
   * @var bool
   *
   * @see \Drupal\sparql_entity_storage\SparqlEntityStorageGraphHandlerInterface::getEntityTypeDefaultGraphIds()
   */
  protected $solutionInOfficialGraph;

  /**
   * The SPARQL connection class.
   *
   * @var \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface
   */
  protected $connection;

  /**
   * The graph handler service.
   *
   * @var \Drupal\sparql_entity_storage\SparqlEntityStorageGraphHandlerInterface
   */
  protected $graphHandler;

  /**
   * The SPARQL storage class.
   *
   * @var \Drupal\sparql_entity_storage\SparqlEntityStorageInterface
   */
  protected $sparqlStorage;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    if ($this->getEntity()->id()) {
      foreach ($this->getAffiliation() as $delta => $collection_id) {
        $this->list[$delta] = $this->createItem($delta, ['target_id' => $collection_id]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(): void {
    parent::preSave();

    // A solution cannot be saved without having a parent collection.
    if ($this->isEmpty()) {
      // We enforce data integrity only for solutions from the 'official'
      // graphs. Solutions stored in other graphs, can live temporary without a
      // parent collection. The code that is handling such cases is responsible
      // to ensure data integrity. A use case is the data federation, where the
      // imported solutions are stored in a 'nonofficial' graph and, temporary,
      // orphan solutions are allowed. See the 'joinup_federation' module for
      // more details.
      if ($this->solutionInOfficialGraph()) {
        throw new \Exception("Solution '{$this->getEntity()->id()}' should have a parent collection.");
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update): bool {
    // This field can be also set when the solution in in one of the 'official'
    // graphs. The code dealing with solutions stored in other graphs is
    // responsible for establishing the relation between the solution and the
    // parent collection. This is useful when solutions are allowed to be
    // temporary stored without a parent collection. Such a case is data
    // federation. See the 'joinup_federation' module for more details.
    if ($this->solutionInOfficialGraph()) {
      $this->updateAffiliation();
    }
    return parent::postSave($update);
  }

  /**
   * Returns the affiliation of the solution host entity.
   *
   * @return string[]
   *   A list of collection IDs where the solution host entity is affiliated.
   */
  protected function getAffiliation(): array {
    return array_values($this->entityTypeManager->getStorage('rdf_entity')
      ->getQuery()
      ->condition('rid', 'collection')
      ->condition('field_ar_affiliates', $this->getEntity()->id())
      ->execute());
  }

  /**
   * Updates the solution affiliation.
   *
   * Instead of using the Drupal Entity API we're operating on a lower level, in
   * order the preserve the collection's changed time. We use direct SPARQL
   * queries to update the solution affiliation, then we clear the affected
   * collection cache in order to reflect the changes on Drupal API level.
   */
  protected function updateAffiliation(): void {
    /** @var \Drupal\rdf_entity\RdfInterface $solution */
    $solution = $this->getEntity();
    $connection = $this->getSparqlConnection();
    $graph_uris = $this->getAvailableGraphs();

    /** @var \Drupal\sparql_entity_storage\SparqlEntityStorageFieldHandlerInterface $field_handler */
    $field_handler = \Drupal::service('sparql.field_handler');
    $field_uri = $field_handler->getFieldPredicates('rdf_entity', 'field_ar_affiliates')['collection'];

    $affected_ids = [];
    foreach ($graph_uris as $graph_uri) {
      // Get existing affiliation.
      $select = [];
      $select[] = 'SELECT ?id';
      $select[] = "FROM <{$graph_uri}>";
      $select[] = 'WHERE {';
      $select[] = "?id <{$field_uri}> <{$solution->id()}> .";
      // Ensure the entity exists in the graph.
      $select[] = "?id a ?type .";
      $select[] = '}';
      $select[] = "ORDER BY (?id)";

      $existing_ids = [];
      foreach ($connection->query(implode("\n", $select)) as $item) {
        $existing_ids[] = (string) $item->id;
      }

      $new_ids = array_map(function (EntityReferenceItem $field_item): string {
        return $field_item->target_id;
      }, $this->list);
      sort($new_ids);

      // The affiliation has been preserved. Exit here.
      if ($new_ids === $existing_ids) {
        return;
      }

      // Delete existing affiliation.
      $delete = [];
      $delete[] = "WITH <{$graph_uri}>";
      $delete[] = "DELETE { ?id <{$field_uri}> <{$solution->id()}> . }";
      $delete[] = "WHERE { ?id <{$field_uri}> <{$solution->id()}> . }";
      $connection->query(implode("\n", $delete));

      // Insert new affiliation.
      $insert = [];
      $insert[] = "WITH <{$graph_uri}>";
      $insert[] = 'INSERT {';
      foreach ($new_ids as $id) {
        $insert[] = "  <{$id}> <{$field_uri}> <{$solution->id()}> .";
      }
      $insert[] = '}';
      $connection->query(implode("\n", $insert));

      // Clear the cache of collections that were affected by changes.
      $affected_ids += array_unique(array_merge($new_ids, $existing_ids));
    }

    if ($affected_ids) {
      $this->getSparqlStorage()->resetCache($affected_ids);
    }
  }

  /**
   * Checks if the solution belongs to one of the 'official' graphs.
   *
   * @return bool
   *   If the solution belongs to one of the 'official' graphs.
   */
  protected function solutionInOfficialGraph(): bool {
    if (!isset($this->solutionInOfficialGraph)) {
      $graph_id = $this->getEntity()->get('graph')->target_id;
      /** @var \Drupal\sparql_entity_storage\SparqlEntityStorageGraphHandlerInterface $graph_handler */
      $graph_handler = \Drupal::service('sparql.graph_handler');
      $this->solutionInOfficialGraph = in_array($graph_id, $graph_handler->getEntityTypeDefaultGraphIds('rdf_entity'));
    }
    return $this->solutionInOfficialGraph;
  }

  /**
   * Returns the graph ids that the collection exists in.
   *
   * @return array
   *   An array of graph URIs that the collection exists in indexed by graph id.
   */
  protected function getAvailableGraphs(): array {
    $graph_handler = $this->getGraphHandler();
    $graph_ids = $graph_handler->getEntityTypeGraphIds('rdf_entity');
    $return = [];

    foreach ($graph_ids as $graph_id) {
      $return[$graph_id] = $graph_handler->getBundleGraphUri('rdf_entity', 'collection', $graph_id);
    }

    return $return;
  }

  /**
   * Loads and returns the SPARQL connection class.
   *
   * @return \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface
   *   The SPARQL connection class.
   */
  protected function getSparqlConnection(): ConnectionInterface {
    if (empty($this->connection)) {
      $this->connection = \Drupal::service('sparql.endpoint');
    }
    return $this->connection;
  }

  /**
   * Loads and returns the graph handler service.
   *
   * @return \Drupal\sparql_entity_storage\SparqlEntityStorageGraphHandlerInterface
   *   The graph handler service.
   */
  protected function getGraphHandler(): SparqlEntityStorageGraphHandlerInterface {
    if (empty($this->graphHandler)) {
      $this->graphHandler = \Drupal::service('sparql.graph_handler');
    }
    return $this->graphHandler;
  }

  /**
   * Loads and returns the graph handler service.
   *
   * @return \Drupal\sparql_entity_storage\SparqlEntityStorageInterface
   *   The graph handler service.
   */
  protected function getSparqlStorage(): SparqlEntityStorageInterface {
    if (empty($this->sparqlStorage)) {
      $this->sparqlStorage = \Drupal::service('entity_type.manager')->getStorage('rdf_entity');
    }
    return $this->sparqlStorage;
  }

}
