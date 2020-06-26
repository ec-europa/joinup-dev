<?php

declare(strict_types = 1);

namespace Drupal\solution\EventSubscriber;

use Drupal\collection\Entity\CollectionInterface;
use Drupal\joinup_group\Event\AddGroupContentEvent;
use Drupal\joinup_group\EventSubscriber\AddGroupContentEventSubscriberBase;

/**
 * Subscribes to Joinup Group events.
 */
class SolutionGroupSubscriber extends AddGroupContentEventSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function addLinks(AddGroupContentEvent $event): void {
    if ($event->getGroup() instanceof CollectionInterface) {
      parent::addLinks($event);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getBundles(): array {
    return [
      'rdf_entity' => [
        'solution',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getRouteName(): string {
    return 'solution.collection_solution.add';
  }

  /**
   * {@inheritdoc}
   */
  protected function getRouteParameters(AddGroupContentEvent $event, string $entity_type_id, string $bundle_id): array {
    return [
      'rdf_entity' => $event->getGroup()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected static function getPriority(): int {
    return 90;
  }

}
