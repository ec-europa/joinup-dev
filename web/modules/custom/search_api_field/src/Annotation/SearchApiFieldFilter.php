<?php

declare(strict_types = 1);

namespace Drupal\search_api_field\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the search api field filter plugin annotation.
 *
 * Filters can be created to support a list of Search API data types or
 * specific fields. Only one support at the time can be declared.
 *
 * Example definition for a filter that supports string data types and one that
 * supports a specific field, specified by machine name.
 *
 * @code
 * @SearchApiFieldFilter(
 *   id = "text",
 *   label = @Translation("Text"),
 *   data_types = {
 *     "fulltext",
 *     "string"
 *   }
 * )
 *
 * @SearchApiFieldFilter(
 *   id = "node_bundle",
 *   label = @Translation("Node bundle"),
 *   fields = {
 *     "entity_bundle",
 *   }
 * )
 * @endcode
 *
 * @Annotation
 */
class SearchApiFieldFilter extends Plugin {

  /**
   * An array of Search API data types the filter supports.
   *
   * @var array
   */
  public $data_types = []; // @codingStandardsIgnoreLine

  /**
   * An array of Search API field machine names that the filter supports.
   *
   * @var array
   */
  public $fields = [];

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
