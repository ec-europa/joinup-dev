<?php

declare(strict_types = 1);

namespace Drupal\joinup_group;

use Drupal\Core\Queue\QueueFactory;
use Drupal\joinup_group\Entity\GroupInterface;

/**
 * Default implementation of 'joinup_group.url_alias_updater' service.
 */
class JoinupGroupContentUrlAliasUpdater implements JoinupGroupContentUrlAliasUpdaterInterface {

  /**
   * The queue factory service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Constructs a new service instance.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory service.
   */
  public function __construct(QueueFactory $queue_factory) {
    $this->queueFactory = $queue_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function queueGroupContent(GroupInterface $group): void {
    $group_hash = md5($group->id());

    /** @var \Drupal\joinup_group\Queue\JoinupGroupQueue $queue */
    $queue = $this->queueFactory->get('joinup_group_queue');

    // Optimisation: There might be other URL alias updates, for this group,
    // already queued as an effect of a previous update. Those updates are no
    // more actual, we are only interested in this later update.
    $queue->deleteGroupItems($group_hash);

    foreach ($group->getGroupContentIds() as $entity_type_id => $entity_ids) {
      foreach ($entity_ids as $entity_id) {
        $queue->createItem([
          'group_hash' => $group_hash,
          'entity_type_id' => $entity_type_id,
          'entity_id' => $entity_id,
        ]);
      }
    }
  }

}
