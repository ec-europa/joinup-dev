<?php

/**
 * @file
 * Views hook implementations for the Joinup search module.
 */

declare(strict_types = 1);

use Drupal\search_api\Entity\Index;

/**
 * Implements hook_views_data_alter().
 *
 * Adds definitions to support rendering links to the canonical page of
 * entities.
 */
function joinup_search_views_data_alter(array &$data) {
  // Create an empty alias tracker variable to mimic the alias generation from
  // search_api_views_data().
  $alias_tracker = [];

  foreach (Index::loadMultiple() as $index) {
    /** @var \Drupal\search_api\IndexInterface $index */
    $datasource_tables_prefix = 'search_api_datasource_' . $index->id() . '_';
    foreach ($index->getDatasources() as $datasource_id => $datasource) {
      $table_key = _search_api_views_find_field_alias($datasource_tables_prefix . $datasource_id, $alias_tracker);
      // Mark the table key alias as used.
      $alias_tracker[$table_key] = TRUE;

      $entity_type_id = $datasource->getEntityTypeId();
      if (!$entity_type_id) {
        continue;
      }

      /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
      $entity_type = \Drupal::service('entity_type.manager')->getDefinition($entity_type_id);
      if (!$entity_type->hasLinkTemplate('canonical')) {
        continue;
      }

      $t_arguments = ['@entity_type_label' => $entity_type->getLabel()];
      $data[$table_key]['view_' . $entity_type_id] = [
        'field' => [
          'title' => t('Link to @entity_type_label', $t_arguments),
          'help' => t('Provide a view link to the @entity_type_label.', $t_arguments),
          'id' => 'search_api_entity_link',
          'real field' => "entity:{$entity_type_id}/_object",
        ],
      ];
    }
  }
}
