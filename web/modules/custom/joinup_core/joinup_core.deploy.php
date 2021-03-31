<?php

/**
 * @file
 * Deploy functions for Joinup.
 *
 * This should only contain update functions that rely on the Drupal API and
 * need to run _after_ the configuration is imported.
 *
 * This is applicable in most cases. However in case the update code enables
 * some functionality that is required for configuration to be successfully
 * imported, it should instead be placed in joinup_core.post_update.php.
 */

declare(strict_types = 1);

/**
 * Switch the filter format of the collection abstract to basic HTML.
 */
function joinup_core_deploy_0107000(array &$sandbox): string {
  $storage = \Drupal::entityTypeManager()->getStorage('rdf_entity');

  if (!isset($sandbox['total'])) {
    $query = $storage->getQuery()
      ->condition('rid', 'collection')
      ->exists('field_ar_abstract');
    $sandbox['ids'] = array_values($query->execute());
    $sandbox['total'] = count($sandbox['ids']);
    $sandbox['processed'] = 0;
  }

  $ids = array_splice($sandbox['ids'], 0, 19);
  /** @var \Drupal\collection\Entity\CollectionInterface[] $collections */
  $collections = $storage->loadMultiple($ids);
  foreach ($collections as $collection) {
    $collection->field_ar_abstract->format = 'basic_html';
    $collection->save();

  }
  $sandbox['processed'] += count($ids);
  $sandbox['#finished'] = empty($sandbox['ids']) ? 1 : $sandbox['processed'] / $sandbox['total'];

  return "Processed {$sandbox['processed']} out of {$sandbox['total']}";
}
