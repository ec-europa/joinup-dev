<?php

namespace Drupal\search_api_arbitrary_facet\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for Arbitrary facet plugins.
 */
abstract class ArbitraryFacetBase extends PluginBase implements ArbitraryFacetInterface {
  use StringTranslationTrait;

  /**
   * The definition of the arbitrary facet.
   */
  public function getFacetDefinition() {}

}
