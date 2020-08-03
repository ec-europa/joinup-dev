<?php

declare(strict_types = 1);

namespace Drupal\asset_release\EventSubscriber;

use Drupal\joinup_group\Event\AddGroupContentEvent;
use Drupal\joinup_group\EventSubscriber\AddGroupContentEventSubscriberBase;
use Drupal\solution\Entity\SolutionInterface;

/**
 * Subscribes to Joinup Group events.
 */
class ReleaseGroupSubscriber extends AddGroupContentEventSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function addLinks(AddGroupContentEvent $event): void {
    if ($event->getGroup() instanceof SolutionInterface) {
      parent::addLinks($event);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getBundles(): array {
    return [
      'rdf_entity' => [
        'asset_release',
      ],
    ];
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
  protected function getRouteName(): string {
    return 'asset_release.solution_asset_release.add';
  }

  /**
   * {@inheritdoc}
   */
  protected static function getPriority(): int {
    return 80;
  }

}
