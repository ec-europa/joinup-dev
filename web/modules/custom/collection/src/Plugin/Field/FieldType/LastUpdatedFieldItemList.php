<?php

namespace Drupal\collection\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Represents a collection last update field.
 */
class LastUpdatedFieldItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  public function computeValue() {
    $this->list[0] = $this->createItem(0, $this->collectionLastUpdate());
  }

  /**
   * Determines when the last change was made to the collection content.
   *
   * The last change time is determined by computing the maximum value between:
   * - The collection changed timestamp.
   * - The highest timestamp of the collection solutions.
   * - The highest timestamp of the collection community content and custom
   *   pages.
   *
   * @return int
   *   Last updated timestamp.
   */
  protected function collectionLastUpdate() {
    /** @var \Drupal\rdf_entity\RdfInterface $collection */
    $collection = $this->getEntity();

    // Store the collection changed timestamp.
    $last_updated = $collection->getChangedTime();

    // Check for a higher child solution changed timestamp.
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $solutions */
    $solutions = $collection->get('field_ar_affiliates');
    /** @var \Drupal\rdf_entity\RdfInterface $solution */
    foreach ($solutions->referencedEntities() as $solution) {
      if ($solution->field_is_state->value === 'validated') {
        if ($solution->getChangedTime() > $last_updated) {
          $last_updated = $solution->getChangedTime();
        }
      }
    }

    // Check for a higher community content or custom page changed timestamp.
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nids = $node_storage->getQuery()
      ->condition('og_audience', $collection->id())
      ->condition('status', TRUE)
      ->sort('changed', 'DESC')
      ->range(0, 1)
      ->execute();
    if ($nids) {
      /** @var \Drupal\node\NodeInterface $node */
      $node = $node_storage->load(array_pop($nids));
      if ($node && $node->getChangedTime() > $last_updated) {
        $last_updated = $node->getChangedTime();
      }
    }

    return $last_updated;
  }

}
