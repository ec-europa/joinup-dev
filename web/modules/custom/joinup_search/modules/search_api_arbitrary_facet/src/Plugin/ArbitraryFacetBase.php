<?php

declare(strict_types = 1);

namespace Drupal\search_api_arbitrary_facet\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for Arbitrary facet plugins.
 */
abstract class ArbitraryFacetBase extends PluginBase implements ArbitraryFacetInterface, CacheableDependencyInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getFacetDefinition(): array {}

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

}
