<?php

declare(strict_types = 1);

namespace Drupal\solution;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Defines a field item list class for the solution 'collections' field.
 *
 * In ADMS-AP collections point to solutions. The reverse relation would have
 * been more logical, and this is quite inconvenient, especially for the search
 * index. This field computes the reverse relationship.
 */
class SolutionAffiliationFieldItemList extends EntityReferenceFieldItemList {

  use ComputedItemListTrait;

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
      // imported solutions are stored in a 'non-official' graph and, temporary,
      // orphan solutions are allowed. See the 'joinup_federation' module for
      // more details.
      /** @var \Drupal\sparql_entity_storage\SparqlEntityStorageGraphHandlerInterface $graph_handler */
      $graph_handler = \Drupal::service('sparql.graph_handler');
      $graph = $this->getEntity()->get('graph');
      // An empty graph will default to the default graph, which is 'official'.
      if ($graph->isEmpty() || in_array($graph->target_id, $graph_handler->getEntityTypeDefaultGraphIds('rdf_entity'))) {
        throw new \Exception("Solution '{$this->getEntity()->id()}' should have a parent collection.");
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update): bool {
    $this->updateAffiliation();
    return parent::postSave($update);
  }

  /**
   * Returns the affiliation of the solution host entity.
   *
   * @return string[]
   *   A list of collection IDs where the solution host entity is affiliated.
   */
  protected function getAffiliation(): array {
    return array_values(\Drupal::entityQuery('rdf_entity')
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
    /** @var \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface $sparql */
    $sparql = \Drupal::service('sparql.endpoint');
    /** @var \Drupal\rdf_entity\RdfInterface $solution */
    $solution = $this->getEntity();
    /** @var \Drupal\sparql_entity_storage\SparqlEntityStorageGraphHandlerInterface $graph_handler */
    $graph_handler = \Drupal::service('sparql.graph_handler');
    $graph_uri = $graph_handler->getBundleGraphUri('rdf_entity', 'collection', $solution->get('graph')->target_id);
    /** @var \Drupal\sparql_entity_storage\SparqlEntityStorageFieldHandlerInterface $field_handler */
    $field_handler = \Drupal::service('sparql.field_handler');
    $field_uri = $field_handler->getFieldPredicates('rdf_entity', 'field_ar_affiliates')['collection'];

    // Get existing affiliation.
    $select[] = 'SELECT ?id';
    $select[] = "FROM <{$graph_uri}>";
    $select[] = "WHERE { ?id <{$field_uri}> <{$solution->id()}> . }";
    $select[] = "ORDER BY (?id)";
    $existing_ids = [];
    foreach ($sparql->query(implode("\n", $select)) as $item) {
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
    $delete[] = "WITH <{$graph_uri}>";
    $delete[] = "DELETE { ?id <{$field_uri}> <{$solution->id()}> . }";
    $delete[] = "WHERE { ?id <{$field_uri}> <{$solution->id()}> . }";
    $sparql->query(implode("\n", $delete));

    // Insert new affiliation.
    $insert[] = "WITH <{$graph_uri}>";
    $insert[] = 'INSERT {';
    foreach ($new_ids as $id) {
      $insert[] = "  <{$id}> <{$field_uri}> <{$solution->id()}> .";
    }
    $insert[] = '}';
    $sparql->query(implode("\n", $insert));

    // Clear the cache of collections that were affected by changes.
    $affected_ids = array_unique(array_merge($new_ids, $existing_ids));
    \Drupal::entityTypeManager()->getStorage('rdf_entity')->resetCache($affected_ids);
  }

}
