<?php

/**
 * @file
 * Token support for the Joinup Solution module.
 */

declare(strict_types = 1);

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_tokens_alter().
 */
function solution_tokens_alter(array &$replacements, array $context, BubbleableMetadata $bubbleable_metadata): void {
  if ($context['type'] === 'rdf_entity' && !empty($context['data']['rdf_entity'])) {
    /** @var \Drupal\rdf_entity\RdfInterface $solution */
    $solution = $context['data']['rdf_entity'];
    if ($solution->bundle() === 'solution' && isset($context['tokens']['collection'])) {
      /** @var \Drupal\rdf_entity\RdfInterface $collection */
      $collection = $solution->get('collection')->entity;
      $replacements[$context['tokens']['collection']] = $collection->label();
      $bubbleable_metadata->addCacheableDependency($collection);
    }
  }
}
