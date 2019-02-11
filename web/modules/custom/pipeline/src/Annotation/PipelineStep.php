<?php

namespace Drupal\pipeline\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a pipeline step item annotation object.
 *
 * @see \Drupal\pipeline\Plugin\PipelineStepPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class PipelineStep extends Plugin {

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
