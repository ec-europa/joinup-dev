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
    $group_id = $group->id();

    /** @var \Drupal\joinup_group\Queue\JoinupGroupQueue $group_queue */
    $group_queue = $this->queueFactory->get('joinup_group:group_update');
    /** @var \Drupal\joinup_group\Queue\JoinupGroupQueue $group_content_queue */
    $group_content_queue = $this->queueFactory->get('joinup_group:group_content_update');

    // Optimisation: There might be other URL alias updates, for this group,
    // already queued as an effect of a previous update. Those updates are no
    // more actual, we are only interested in this later update.
    $group_queue->deleteGroupItems($group_id);
    $group_content_queue->deleteGroupItems($group_id);

    $group_queue->createItem([
      'group_id' => $group_id,
      // The above 'group_id' is add only to compute the hash and is removed
      // from queue item data afterwards.
      // @see \Drupal\joinup_group\Queue\JoinupGroupQueue::doCreateItem()
      'entity_id' => $group_id,
    ]);
  }

}
