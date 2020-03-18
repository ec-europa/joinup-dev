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
function joinup_group_token_info(): array {
  return [
    'tokens' => [
      'rdf_entity' => [
        'parent_collection' => [
          'name' => t('Parent collection'),
          'description' => t('Returns the parent collection for any solution, release or distribution.'),
          'module' => 'joinup_group',
          'type' => 'rdf_entity',
        ],
      ],
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function joinup_group_tokens(string $type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
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

  $token_service = \Drupal::token();
  /** @var \Drupal\joinup_core\JoinupRelationManagerInterface $relation_manager */
  $relation_manager = \Drupal::service('joinup_core.relations_manager');
  if (isset($tokens['parent_collection'])) {
    $original = $tokens['parent_collection'];
    $parent_solution = $rdf_entity;
    if ($rdf_entity->bundle() !== 'solution') {
      $parent_solution = $relation_manager->getParent($rdf_entity);
    }

    if (empty($parent_solution) || $parent_solution->collection->isEmpty()) {
      return $replacements;
    }
    $bubbleable_metadata->addCacheableDependency($parent_solution);
    /** @var \Drupal\rdf_entity\RdfInterface $collection */
    if ($collection = $parent_solution->collection->first()->entity) {
      $bubbleable_metadata->addCacheableDependency($collection);
      $replacements[$original] = $collection->label();
      // Allow to descend further into the solution's parent collection.
      if ($collection_tokens = $token_service->findwithPrefix($tokens, 'parent_collection')) {
        $replacements += $token_service->generate('rdf_entity', $collection_tokens, ['rdf_entity' => $collection], $options, $bubbleable_metadata);
      }
    }
  }

  return $replacements;
}
