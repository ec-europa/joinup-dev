<?php

namespace Drupal\pipeline;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions for defined pipelines.
 */
class PipelinePermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The pipeline plugin manager service.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pipelinePluginManager;

  /**
   * Constructs a new permission generator instance.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $pipeline_plugin_manager
   *   The pipeline plugin manager service.
   */
  public function __construct(PluginManagerInterface $pipeline_plugin_manager) {
    $this->pipelinePluginManager = $pipeline_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.pipeline_pipeline'));
  }

  /**
   * Returns an array of node type permissions.
   *
   * @return array[]
   *   The pipeline execution permissions.
   */
  public function executePipeline() {
    $permissions = [];

    /** @var \Drupal\pipeline\Plugin\PipelinePipelineInterface[] $definitions */
    $definitions = $this->pipelinePluginManager->getDefinitions();
    foreach ($definitions as $plugin_id => $definition) {
      $arguments = ['%pipeline' => $definition['label']];
      $permissions["execute $plugin_id pipeline"] = [
        'title' => $this->t('Execute the %pipeline pipeline', $arguments),
        'description' => $this->t('Allows users granted with this permissions ro run the %pipeline pipeline.', $arguments),
        // @todo Eventually extract this value from the plugin annotation.
        'restrict access' => TRUE,
      ];
    }

    return $permissions;
  }

}
