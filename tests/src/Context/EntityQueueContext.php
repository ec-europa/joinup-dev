<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\entityqueue\EntitySubqueueInterface;

/**
 * Behat step definitions related to entity queues.
 */
class EntityQueueContext extends RawDrupalContext {

  /**
   * A variable to hold entity queues and given terms.
   *
   * The variable will be a list of term IDs indexed by the entity queue ID and
   * will be used in the post scenario method to revert the values.
   *
   * @var array
   */
  protected $entityQueues = [];

  /**
   * Assigns terms to an entity queue.
   *
   * @param string $queue_label
   *   The entity queue label.
   * @param \Behat\Gherkin\Node\TableNode $terms
   *   The labels of the terms to assign.
   *
   * @Given the following terms are assigned to the :queue_label queue:
   */
  public function givenEntityQueueHasTerms(string $queue_label, TableNode $terms): void {
    /** @var \Drupal\entityqueue\EntitySubqueueInterface $entity_queue */
    $entity_queue = $this->loadEntityQueueByLabel($queue_label);
    $term_ids = $this->queueTermLabelsToIds($entity_queue, $terms->getColumn(0));

    $this->entityQueues[$entity_queue->id()] = $entity_queue->get('items')->getValue();
    $entity_queue->set('items', $term_ids);
    $entity_queue->save();
  }

  /**
   * Loads the entity queue by label.
   *
   * @param string $queue_label
   *   The label of the entity queue.
   *
   * @return \Drupal\entityqueue\EntitySubqueueInterface
   *   The loaded entity queue.
   */
  protected function loadEntityQueueByLabel(string $queue_label): EntitySubqueueInterface {
    /** @var \Drupal\entityqueue\EntitySubqueueInterface[] $entity_queues */
    $entity_queues = \Drupal::entityTypeManager()->getStorage('entity_subqueue')->loadByProperties(['title' => $queue_label]);
    if (empty($entity_queues)) {
      throw new \Exception("Entity queue with label {$entity_queues} was not found.");
    }

    return reset($entity_queues);
  }

  /**
   * Converts term labels to IDs.
   *
   * @param \Drupal\entityqueue\EntitySubqueueInterface $entity_subqueue
   *   The entity queue entity.
   * @param string[] $term_labels
   *   The term labels to be inserted.
   *
   * @return array
   *   The converted IDs
   *
   * @throw \InvalidArgumentException
   *   Throws an exception if at least one of the terms are not found.
   */
  protected function queueTermLabelsToIds(EntitySubqueueInterface $entity_subqueue, array $term_labels): array {
    $entity_queue = $entity_subqueue->getQueue();
    $entity_type_id = $entity_queue->getTargetEntityTypeId();
    $bundles = $entity_queue->getEntitySettings()['handler_settings']['target_bundles'] ?? NULL;
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
    $query = $storage->getQuery()->condition('name', $term_labels, 'IN');
    if (!empty($bundles)) {
      $query->condition('vid', $bundles, 'IN');
    }
    $term_ids = $query->execute();
    $terms = $storage->loadMultiple($term_ids);
    $existing = [];
    foreach ($terms as $term) {
      $existing[$term->id()] = $term->label();
    }

    $not_existing = array_diff($term_labels, array_values($existing));
    if (!empty($not_existing)) {
      throw new \InvalidArgumentException('The following terms were not found: ', implode(', ', $not_existing));
    }
    return array_keys($existing);
  }

  /**
   * Resets the items of the entity queues that were altered in the tests.
   *
   * @AfterScenario @terms
   */
  protected function resetEntityQueues(): void {
    if (empty($this->entityQueues)) {
      return;
    }

    foreach ($this->entityQueues as $id => $items) {
      $entity_queue = \Drupal::entityTypeManager()->getStorage('entity_subqueue')->load($id);
      $entity_queue->set('items', $items);
      $entity_queue->save();
    }
  }

}
