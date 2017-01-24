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
   * A two level array with entity types and bundles where this plugin applies.
   *
   * The fist level is either the entity type ID or a list/array of bundles
   * keyed by entity type ID.
   *
   * @var array
   *
   * @code
   *   ...
   *   applyTo = {
   *     "rdf_entity",
   *     "taxonomy_term" = {
   *       "tags",
   *       "topics",
   *     },
   *   },
   * @endcode
   * In this example, the plugin applies to all 'rdf_entity' entities,
   * regardless of the entity bundle and to bundles 'tags' and 'topics' of
   * 'taxonomy_term' entity type.
   */
  public $applyTo = [];

}
