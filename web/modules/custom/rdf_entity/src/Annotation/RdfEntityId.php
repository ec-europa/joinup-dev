<?php

namespace Drupal\rdf_entity\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a entity ID generator plugin annotation.
 *
 * @see plugin_api
 *
 * @ingroup plugin_api
 *
 * @Annotation
 */
class RdfEntityId extends Plugin {

  /**
   * The entity ID generator plugin id.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the entity ID generator plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A two level array with bundles where this plugin applies.
   *
   * The fist level is the entity type and the second, a list of bundles.
   *
   * @var array
   *
   * @code
   *   ...
   *   bundles = {
   *     "taxonomy_term" = {
   *       "tags",
   *       "topics",
   *     },
   *   },
   * @endcode
   */
  public $bundles = [];

}
