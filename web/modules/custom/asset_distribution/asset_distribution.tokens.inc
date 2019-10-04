<?php

/**
 * @file
 * Token support for the Joinup Release module.
 */

declare(strict_types = 1);

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_tokens_alter().
 */
function asset_distribution_tokens_alter(array &$replacements, array $context, BubbleableMetadata $bubbleable_metadata): void {
  if ($context['type'] === 'rdf_entity' && !empty($context['data']['rdf_entity'])) {
    /** @var \Drupal\rdf_entity\RdfInterface $distribution */
    $distribution = $context['data']['rdf_entity'];
    if ($distribution->bundle() === 'asset_distribution' && isset($context['tokens']['collection'])) {
      /** @var \Drupal\rdf_entity\RdfInterface $solution */
      if ($solution = $distribution->get('og_audience')->entity) {
        /** @var \Drupal\rdf_entity\RdfInterface $collection */
        $collection = $solution->get('collection')->entity;
        $replacements[$context['tokens']['collection']] = $collection->label();
        $bubbleable_metadata->addCacheableDependency($collection);
      }
    }
  }
}
