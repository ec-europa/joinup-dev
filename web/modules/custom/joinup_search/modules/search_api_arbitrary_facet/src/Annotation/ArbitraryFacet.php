<?php

namespace Drupal\search_api_arbitrary_facet\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Arbitrary facet item annotation object.
 *
 * @see \Drupal\search_api_arbitrary_facet\Plugin\ArbitraryFacetManager
 * @see plugin_api
 *
 * @Annotation
 */
class ArbitraryFacet extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
