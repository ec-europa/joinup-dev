<?php

/**
 * @file
 * Hook definitions for the Joinup bundle class module.
 */

declare(strict_types = 1);

use Drupal\joinup_event\Event;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alters the list of bundle classes for the given entity type.
 *
 * @param array $bundle_classes
 *   The array of node entity bundle classes to alter.
 * @param string $entity_type
 *   The entity type for which the bundle classes are defined. This is passed by
 *   reference but should not be modified by hook implementations.
 */
function hook_joinup_bundle_class_alter(array &$bundle_classes, string &$entity_type): void {
  if ($entity_type === 'node') {
    $bundle_classes['event'] = Event::class;
  }
}

/**
 * @} End of "addtogroup hooks".
 */
