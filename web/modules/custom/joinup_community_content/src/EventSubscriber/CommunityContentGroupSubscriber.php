<?php

declare(strict_types = 1);

namespace Drupal\joinup_community_content\EventSubscriber;

use Drupal\joinup_group\Event\AddGroupContentEvent;
use Drupal\joinup_group\EventSubscriber\AddGroupContentEventSubscriberBase;

/**
 * Subscribes to Joinup Group events.
 */
class CommunityContentGroupSubscriber extends AddGroupContentEventSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function getBundles(): array {
    return [
      'node' => [
        'news',
        'discussion',
        'document',
        'event',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getRouteName(): string {
    return 'joinup_group.add_content';
  }

  /**
   * {@inheritdoc}
   */
  protected function getRouteParameters(AddGroupContentEvent $event, string $entity_type_id, string $bundle_id): array {
    return [
      'rdf_entity' => $event->getGroup()->id(),
      'node_type' => $bundle_id,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected static function getPriority(): int {
    return 60;
  }

}
