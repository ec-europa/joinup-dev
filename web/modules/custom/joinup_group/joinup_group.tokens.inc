<?php

/**
 * @file
 * Builds placeholder replacement tokens for joinup group related data.
 */

declare(strict_types = 1);

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\joinup_group\JoinupGroupHelper;

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
        'short_id_or_title' => [
          'name' => t('Short ID or title'),
          'description' => t('Returns short ID if available, or the title if the short ID is not.'),
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
  $token_service = \Drupal::token();
  $rdf_entity = $data['rdf_entity'];
  $check_for_parent_collection = isset($tokens['parent_collection']) || $token_service->findWithPrefix($tokens, 'parent_collection');
  $bundles_with_parent_collection = [
    'solution',
    'asset_release',
    'asset_distribution',
  ];
  $has_parent_collection = in_array($rdf_entity->bundle(), $bundles_with_parent_collection);
  $parent_collection = NULL;

  if ($check_for_parent_collection && $has_parent_collection) {
    $parent_solution = $rdf_entity->bundle() === 'solution' ? $rdf_entity : JoinupGroupHelper::getGroup($rdf_entity);
    if (!empty($parent_solution) && !$parent_solution->collection->isEmpty()) {
      $parent_collection = $parent_solution->collection->first()->entity;
    }
  }

  if ($type == 'rdf_entity' && !empty($data['rdf_entity'])) {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'parent_collection':
          $bubbleable_metadata->addCacheableDependency($parent_solution);
          if (empty($parent_collection)) {
            continue 2;
          }
          $bubbleable_metadata->addCacheableDependency($parent_collection);
          $replacements[$original] = $parent_collection->label();
          $bubbleable_metadata->addCacheableDependency($parent_collection);
          break;

        case 'short_id_or_title':
          $replacements[$original] = ($rdf_entity->hasField('field_short_id') && !$rdf_entity->get('field_short_id')->isEmpty())
            ? $rdf_entity->get('field_short_id')->value
            : $rdf_entity->label();
          $bubbleable_metadata->addCacheableDependency($rdf_entity);
          break;
      }
    }
  }

  // Allow to descend further into the solution's parent collection.
  if (($collection_tokens = $token_service->findWithPrefix($tokens, 'parent_collection')) && $has_parent_collection) {
    $replacements += $token_service->generate('rdf_entity', $collection_tokens, ['rdf_entity' => $parent_collection], $options, $bubbleable_metadata);
  }

  return $replacements;
}
