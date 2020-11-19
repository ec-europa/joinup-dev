<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\EventSubscriber;

use Drupal\joinup_group\EventSubscriber\AddGroupContentEventSubscriberBase;

/**
 * Subscribes to Joinup Group events.
 */
class LicenceGroupSubscriber extends AddGroupContentEventSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function getBundles(): array {
    return [
      'rdf_entity' => [
        'licence',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getRouteName(): string {
    return 'joinup_licence.add';
  }

  /**
   * {@inheritdoc}
   */
  protected static function getPriority(): int {
    return 50;
  }

}
