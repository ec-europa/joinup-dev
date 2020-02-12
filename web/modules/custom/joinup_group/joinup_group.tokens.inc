<?php

/**
 * @file
 * Builds placeholder replacement tokens for joinup group related data.
 */

declare(strict_types = 1);

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function joinup_group_token_info() {
  return [
    'tokens' => [
      'rdf_entity' => [
        'parent_collection' => [
          'name' => t('Parent collection'),
          'description' => t('Returns the parent collection for any solution, release or distribution.'),
        ],
      ],
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function joinup_group_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type !== 'rdf_entity' || empty($data['rdf_entity'])) {
    return $replacements;
  }

  /** @var \Drupal\rdf_entity\RdfInterface $rdf_entity */
  $rdf_entity = $data['rdf_entity'];
  $bundles = ['solution', 'asset_release', 'asset_distribution'];
  if (!in_array($rdf_entity->bundle(), $bundles)) {
    return $replacements;
  }

  /** @var \Drupal\joinup_core\JoinupRelationManagerInterface $relation_manager */
  $relation_manager = \Drupal::service('joinup_core.relations_manager');
  foreach ($tokens as $name => $original) {
    if ($name === 'parent_collection') {
      $parent_solution = $rdf_entity;
      if ($rdf_entity->bundle() !== 'solution') {
        $parent_solution = $relation_manager->getParent($rdf_entity);
      }

      if (empty($parent_solution) || $parent_solution->collection->isEmpty()) {
        return $replacements;
      }
      $replacements['[rdf_entity:parent_collection]'] = $parent_solution->collection->first()->target_id;
    }
  }

  return $replacements;
}
