<?php

declare(strict_types = 1);

namespace Drupal\solution;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\rdf_entity\RdfInterface;

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
    return array_values(\Drupal::service('entity_type.manager')
      ->getStorage('rdf_entity')
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
    $connection = \Drupal::service('sparql.endpoint');
    $graph_uris = $this->getAvailableGraphs();

    /** @var \Drupal\sparql_entity_storage\SparqlEntityStorageFieldHandlerInterface $field_handler */
    $field_handler = \Drupal::service('sparql.field_handler');
    $field_uri = $field_handler->getFieldPredicates('rdf_entity', 'field_ar_affiliates')['collection'];

    // Generate a list of new ids that the solution references as an affiliate.
    $new_ids = array_map(function (EntityReferenceItem $field_item): string {
      return $field_item->target_id;
    }, $this->list);
    sort($new_ids);

    $affected_ids = [];
    $existing_ids = [];

    // Get existing affiliation.
    $select = [];
    $select[] = 'SELECT ?id';
    foreach ($graph_uris as $graph_uri) {
      $select[] = "FROM <{$graph_uri}>";
    }
    $select[] = 'WHERE {';
    $select[] = "?id <{$field_uri}> <{$solution->id()}> .";
    // Ensure the entity exists in the graph.
    $select[] = "?id a ?type .";
    $select[] = '}';
    $select[] = "ORDER BY (?id)";

    foreach ($connection->query(implode("\n", $select)) as $item) {
      $existing_ids[(string) $item->id] = (string) $item->id;
    }

    // The affiliation has been preserved. Continue to the rest of the graphs.
    if ($new_ids === array_values($existing_ids)) {
      return;
    }

    // Collect the ids of the collections that were affected by changes.
    $affected_ids += array_unique(array_merge($new_ids, $existing_ids));

    $connection->query($this->getDeleteQuery($graph_uris, $field_uri, $solution, $new_ids));
    $connection->query($this->getInsertQuery($graph_uris, $field_uri, $solution, $new_ids));

    if ($affected_ids) {
      // Clear the cache of collections that were affected by changes.
      \Drupal::service('entity_type.manager')->getStorage('rdf_entity')->resetCache($affected_ids);
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
    $graph_handler = \Drupal::service('sparql.graph_handler');
    $return = [];

    foreach (['default', 'draft'] as $graph_id) {
      $return[$graph_id] = $graph_handler->getBundleGraphUri('rdf_entity', 'collection', $graph_id);
    }

    return $return;
  }

  /**
   * Retrieves the delete query.
   *
   * The following method will produce a query in the following form.
   *
   * @codingStandardsIgnoreStart
   * DELETE {
   *   GRAPH <g1> {
   *     a b c
   *   }
   *   GRAPH <g2> {
   *     a b c
   *   }
   * }
   * USING <g1>
   * USING <g2>
   * WHERE { ... }
   * @codingStandardsIgnoreEnd
   *
   * @param array $graph_uris
   *   A list of graph uris.
   * @param string $field_predicate
   *   The field predicate.
   * @param \Drupal\rdf_entity\RdfInterface $solution
   *   The solution entity.
   * @param array $new_ids
   *   The new affiliates list.
   *
   * @return string
   *   The query string that updates the values.
   */
  protected function getDeleteQuery(array $graph_uris, string $field_predicate, RdfInterface $solution, array $new_ids): string {
    $query_parts = [];
    $query_parts[] = "DELETE {";
    foreach ($graph_uris as $uri) {
      $query_parts[] = "GRAPH <{$uri}> {";
      $query_parts[] = "?id <{$field_predicate}> <{$solution->id()}> .";
      $query_parts[] = '}';
    }
    $query_parts[] = '}';
    foreach ($graph_uris as $uri) {
      $query_parts[] = "USING <{$uri}>";
    }
    $query_parts[] = "WHERE { ?id <{$field_predicate}> <{$solution->id()}> }";

    return implode("\n", $query_parts);
  }

  /**
   * Retrieves the insert query.
   *
   * The following method will produce a query in the following form.
   *
   * @codingStandardsIgnoreStart
   * INSERT {
   *   GRAPH <g1> {
   *     x y z
   *   }
   *   GRAPH <g2> {
   *     x y z
   *   }
   * }
   * @codingStandardsIgnoreEnd
   *
   * @param array $graph_uris
   *   A list of graph uris.
   * @param string $field_predicate
   *   The field predicate.
   * @param \Drupal\rdf_entity\RdfInterface $solution
   *   The solution entity.
   * @param array $new_ids
   *   The new affiliates list.
   *
   * @return string
   *   The query string that updates the values.
   */
  protected function getInsertQuery(array $graph_uris, string $field_predicate, RdfInterface $solution, array $new_ids): string {
    $query_parts = [];
    $query_parts[] = 'INSERT {';
    foreach ($graph_uris as $uri) {
      $query_parts[] = "GRAPH <{$uri}> {";
      foreach ($new_ids as $id) {
        $query_parts[] = "<{$id}> <{$field_predicate}> <{$solution->id()}> .";
      }
      $query_parts[] = '}';
    }
    $query_parts[] = '}';
    return implode("\n", $query_parts);
  }

}
