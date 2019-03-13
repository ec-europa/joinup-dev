<?php

namespace Drupal\pipeline\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a pipeline item annotation object.
 *
 * @see \Drupal\pipeline\Plugin\PipelinePipelinePluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class PipelinePipeline extends Plugin {

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

  /**
   * The list of steps for this pipeline.
   *
   * @var string[]
   */
  public $steps;

}
