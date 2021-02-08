<?php

/**
 * @file
 * Builds placeholder replacement tokens for joinup group related data.
 */

declare(strict_types = 1);

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\collection\Entity\CollectionContentInterface;
use Drupal\collection\Entity\CollectionInterface;
use Drupal\collection\Exception\MissingCollectionException;
use Drupal\joinup_bundle_class\ShortIdInterface;
use Drupal\solution\Entity\SolutionContentInterface;
use Drupal\solution\Exception\MissingSolutionException;

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

  /** @var \Drupal\rdf_entity\RdfInterface $rdf_entity */
  $rdf_entity = $data['rdf_entity'];
  if (isset($tokens['bundle_singular'])) {
    $original = $tokens['bundle_singular'];
    $bubbleable_metadata->addCacheableDependency($rdf_entity);
    $replacements[$original] = $rdf_entity->{$rdf_entity->getEntityType()->getKey('bundle')}->entity->getSingularLabel();
  }

  $token_service = \Drupal::token();
  $rdf_entity = $data['rdf_entity'];

  $get_parent_collection = function (CollectionContentInterface $rdf_entity): ?CollectionInterface {
    try {
      return $rdf_entity->getCollection();
    }
    catch (MissingCollectionException $e) {
      // Unexpected case of a missing collection for group content. Let's log an
      // error but this is not fatal so allow the token generation to continue.
      \Drupal::logger('joinup_group')->error('Failed to generate token for RDF entity with ID @id due to missing parent collection', ['@id' => $rdf_entity->id()]);
    }
    return NULL;
  };

  if ($type == 'rdf_entity' && !empty($data['rdf_entity'])) {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'parent_collection':
          // If the entity is solution content then it is related to the parent
          // collection through the solution. Add a dependency on the solution
          // because if the solution moves to a different collection this
          // affects the token value.
          if ($rdf_entity instanceof SolutionContentInterface) {
            try {
              $bubbleable_metadata->addCacheableDependency($rdf_entity->getSolution());
            }
            catch (MissingSolutionException $e) {
              // It is not expected that the solution is missing for solution
              // content. Let's log an error but this is not fatal so allow the
              // token generation to continue.
              \Drupal::logger('joinup_group')->error('Failed to generate token for RDF entity with ID @id due to missing parent solution', ['@id' => $rdf_entity->id()]);
              break;
            }
          }
          if ($rdf_entity instanceof CollectionContentInterface && $parent_collection = $get_parent_collection($rdf_entity)) {
            $bubbleable_metadata->addCacheableDependency($parent_collection);
            $replacements[$original] = $parent_collection->label();
          }
          break;

        case 'short_id_or_title':
          $replacements[$original] = ($rdf_entity instanceof ShortIdInterface && $rdf_entity->hasShortId())
            ? $rdf_entity->getShortId()
            : $rdf_entity->label();
          $bubbleable_metadata->addCacheableDependency($rdf_entity);
          break;
      }
    }
  }

  // Allow to descend further into the solution's parent collection.
  if (($collection_tokens = $token_service->findWithPrefix($tokens, 'parent_collection')) && $rdf_entity instanceof CollectionContentInterface && $collection = $get_parent_collection($rdf_entity)) {
    $replacements += $token_service->generate('rdf_entity', $collection_tokens, ['rdf_entity' => $collection], $options, $bubbleable_metadata);
  }

  return $replacements;
}
