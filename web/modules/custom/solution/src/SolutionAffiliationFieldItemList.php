<?php

declare(strict_types = 1);

namespace Drupal\solution;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\rdf_entity\Database\Driver\sparql\ConnectionInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfEntityGraphInterface;
use Drupal\rdf_entity\RdfGraphHandlerInterface;
use Drupal\rdf_entity\RdfInterface;

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
   * The SPARQL endpoint.
   *
   * @var \Drupal\rdf_entity\Database\Driver\sparql\ConnectionInterface
   */
  protected $sparqlEndpoint;

  /**
   * The graph handler service.
   *
   * @var \Drupal\rdf_entity\RdfGraphHandlerInterface
   */
  protected $graphHandler;

  /**
   * A list og graph URIs keyed by graph ID.
   *
   * @var string[]
   */
  protected $graphUris = [];

  /**
   * The 'field_ar_affiliates' predicate.
   *
   * @var string
   */
  protected $affiliatesPredicate;

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
  public function postSave($update) {
    $solution_id = $this->getEntity()->id();

    if (!empty($this->list)) {
      $collection_ids = array_map(function (EntityReferenceItem $field_item): string {
        return $field_item->target_id;
      }, $this->list);
      // Ensure no duplicates.
      $collection_ids = array_values(array_unique($collection_ids));
    }
    else {
      // It's possible we land here without the field being computed. If this is
      // en existing entity, get affiliation from the backend.
      $collection_ids = $solution_id ? $this->getAffiliation() : [];
    }

    // Optimize when the solution doesn't have yet an ID.
    $existing_collection_ids = $solution_id ? $this->getAffiliation() : [];

    // Update collections where this solution is no more affiliate.
    if ($removed_collection_ids = array_diff($existing_collection_ids, $collection_ids)) {
      /** @var \Drupal\rdf_entity\RdfInterface $collection */
      foreach (Rdf::loadMultiple($removed_collection_ids, [RdfEntityGraphInterface::DEFAULT, 'draft']) as $collection) {
        $this->removeAffiliate($collection, $solution_id);
      }
    }

    // Update collections where this solution is newly affiliated.
    if ($new_collection_ids = array_diff($collection_ids, $existing_collection_ids)) {
      /** @var \Drupal\rdf_entity\RdfInterface $collection */
      foreach (Rdf::loadMultiple($new_collection_ids) as $id => $collection) {
        if ($collection->bundle() !== 'collection') {
          throw new \Exception('Only collections can be referenced in affiliation requests.');
        }
        $this->addAffiliate($collection, $solution_id);
      }
    }

    // Clear the cache if at least one collection has been changed.
    if ($changed_collection_ids = array_merge($removed_collection_ids, $new_collection_ids)) {
      \Drupal::entityTypeManager()->getStorage('rdf_entity')->resetCache($changed_collection_ids);
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
    return array_values(\Drupal::entityQuery('rdf_entity')
      ->condition('rid', 'collection')
      ->condition('field_ar_affiliates', $this->getEntity()->id())
      ->execute());
  }

  /**
   * Removes an affiliated solution from a collection.
   *
   * Cannot use the Drupal entity API here as this would update the value of the
   * 'changed' collection field. Preserving the 'changed' value while saving to
   * be fixed in core in https://www.drupal.org/project/drupal/issues/2329253.
   *
   * @param \Drupal\rdf_entity\RdfInterface $collection
   *   The collection.
   * @param string $solution_id
   *   The affiliated solution ID.
   *
   * @see https://www.drupal.org/project/drupal/issues/2329253
   */
  protected function removeAffiliate(RdfInterface $collection, string $solution_id): void {
    $graph_uri = $this->getGraphUri($collection->graph->target_id);
    $affiliates_uri = $this->getAffiliatesPredicate();
    $this->getSparqlEndpoint()->query("DELETE FROM <$graph_uri> {<{$collection->id()}> <$affiliates_uri> <$solution_id>};");
  }

  /**
   * Adds an affiliated solution to a collection.
   *
   * Cannot use the Drupal entity API here as this would update the value of the
   * 'changed' collection field. Preserving the 'changed' value while saving to
   * be fixed in core in https://www.drupal.org/project/drupal/issues/2329253.
   *
   * @param \Drupal\rdf_entity\RdfInterface $collection
   *   The collection.
   * @param string $solution_id
   *   The affiliated solution ID.
   *
   * @see https://www.drupal.org/project/drupal/issues/2329253
   */
  protected function addAffiliate(RdfInterface $collection, string $solution_id): void {
    $graph_uri = $this->getGraphUri($collection->graph->target_id);
    $affiliates_uri = $this->getAffiliatesPredicate();
    $this->getSparqlEndpoint()->query("INSERT INTO <$graph_uri> {<{$collection->id()}> <$affiliates_uri> <$solution_id>};");
  }

  /**
   * Returns the SPARQL endpoint service.
   *
   * @return \Drupal\rdf_entity\Database\Driver\sparql\ConnectionInterface
   *   The SPARQL connection.
   */
  protected function getSparqlEndpoint(): ConnectionInterface {
    if (!isset($this->sparqlEndpoint)) {
      $this->sparqlEndpoint = \Drupal::service('sparql_endpoint');
    }
    return $this->sparqlEndpoint;
  }

  /**
   * Returns the SPARQL graph handler service.
   *
   * @return \Drupal\rdf_entity\RdfGraphHandlerInterface
   *   The SPARQL graph handler service.
   */
  protected function getGraphHandler(): RdfGraphHandlerInterface {
    if (!isset($this->graphHandler)) {
      $this->graphHandler = \Drupal::service('sparql.graph_handler');
    }
    return $this->graphHandler;
  }

  /**
   * Returns a graph URI, given its ID for collections.
   *
   * @param string $graph_id
   *   The graph ID.
   *
   * @return string
   *   The graph URI.
   */
  protected function getGraphUri(string $graph_id): string {
    if (!isset($this->graphUris[$graph_id])) {
      $this->graphUris[$graph_id] = $this->getGraphHandler()->getBundleGraphUri('rdf_entity', 'collection', $graph_id);
    }
    return $this->graphUris[$graph_id];
  }

  /**
   * Caches and returns the 'field_ar_affiliates' predicate.
   *
   * @return string
   *   The 'field_ar_affiliates' predicate.
   */
  protected function getAffiliatesPredicate(): string {
    if (!isset($this->affiliatesPredicate)) {
      $this->affiliatesPredicate = \Drupal::service('sparql.field_handler')->getFieldPredicates('rdf_entity', 'field_ar_affiliates')['collection'];
    }
    return $this->affiliatesPredicate;
  }

}
