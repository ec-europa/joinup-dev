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

}
