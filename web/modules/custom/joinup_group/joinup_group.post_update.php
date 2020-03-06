<?php

/**
 * @file
 * Post update functions for the joinup_group module.
 */

declare(strict_types = 1);

use Drupal\joinup_group\ContentCreationOptions;

/**
 * Migrate eLibrary data to the new Content creation field.
 */
function joinup_group_post_update_migrate_elibrary(array &$sandbox) {
  $field_mapping = [
    'collection' => [
      'source' => 'field_ar_elibrary_creation',
      'destination' => 'field_ar_content_creation',
    ],
    'solution' => [
      'source' => 'field_is_elibrary_creation',
      'destination' => 'field_is_content_creation',
    ],
  ];

  $elibrary_to_content_creation_mapping = [
    0 => ContentCreationOptions::FACILITATORS,
    1 => ContentCreationOptions::MEMBERS,
    2 => ContentCreationOptions::REGISTERED_USERS,
  ];

  $storage = \Drupal::entityTypeManager()->getStorage('rdf_entity');

  if (!isset($sandbox['entity_ids'])) {
    $bundles = ['collection', 'solution'];
    $sandbox['entity_ids'] = \Drupal::entityQuery('rdf_entity')
      ->condition('rid', $bundles, 'IN')
      ->execute();
    $sandbox['current'] = 0;
    $sandbox['max'] = count($sandbox['entity_ids']);
    $sandbox['errors'] = 0;
  }

  $slice = array_slice($sandbox['entity_ids'], $sandbox['current'], 50);

  /** @var \Drupal\rdf_entity\RdfInterface $entity */
  foreach ($storage->loadMultiple($slice) as $entity) {
    $bundle = $entity->bundle();

    $original_value = $entity->get($field_mapping[$bundle]['source'])->value;
    $updated_value = $elibrary_to_content_creation_mapping[$original_value];

    $entity->set($field_mapping[$bundle]['destination'], $updated_value);
    $entity->skip_notification = TRUE;
    try {
      $entity->save();
    }
    catch (\Exception $e) {
      // Some solutions have lost their collection affiliation and can not be
      // updated. Skip these and keep track of the number of errors.
      // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-5870
      $sandbox['errors']++;
    }

    $sandbox['current']++;
  }

  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['current'] / $sandbox['max']);
  return "Processed {$sandbox['current']} out of {$sandbox['max']}. Errors: {$sandbox['errors']}";
}
