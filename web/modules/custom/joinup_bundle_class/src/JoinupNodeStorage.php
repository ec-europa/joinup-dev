<?php

declare(strict_types = 1);

namespace Drupal\joinup_bundle_class;

use Drupal\node\NodeStorage;

/**
 * Provides custom bundle classes for the node entity storage.
 */
class JoinupNodeStorage extends NodeStorage {

  /**
   * {@inheritdoc}
   */
  public static function bundleClasses() {
    $entity_type = 'node';
    $bundle_classes = parent::bundleClasses();
    \Drupal::moduleHandler()->alter('joinup_bundle_class', $bundle_classes, $entity_type);
    return $bundle_classes;
  }

}
