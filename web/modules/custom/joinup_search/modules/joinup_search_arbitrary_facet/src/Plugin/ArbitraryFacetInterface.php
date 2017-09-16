<?php

namespace Drupal\joinup_search_arbitrary_facet\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Arbitrary facet plugins.
 */
interface ArbitraryFacetInterface extends PluginInspectionInterface {

  /**
   * Returns the arbitrary facet definition.
   *
   * The format of the definition is described in the default plugin.
   *
   * @see \Drupal\joinup_search_arbitrary_facet\Plugin\ArbitraryFacet\DefaultArbitraryFacet
   *
   * @return array
   *   The facet definition.
   */
  public function getFacetDefinition();

}
