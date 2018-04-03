<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a plugin that attached provenance data to the entities.
 *
 * @see \Drupal\rdf_etl\Plugin\AttachProvenanceDataPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class AttachProvenanceData extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The weight of this plugin.
   *
   * The plugin manager will use this definition entry to create an ordered list
   * of plugins, so passes precedence can be determined. If is missed, the 0
   * value is assumed.
   *
   * @var int
   */
  public $weight = 1;

}
