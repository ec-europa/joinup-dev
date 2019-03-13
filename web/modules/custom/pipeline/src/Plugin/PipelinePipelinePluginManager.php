<?php

namespace Drupal\pipeline\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\pipeline\Annotation\PipelinePipeline;

/**
 * Provides the pipeline plugin manager.
 */
class PipelinePipelinePluginManager extends DefaultPluginManager {

  /**
   * Constructs a new plugin manager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/pipeline/Pipeline', $namespaces, $module_handler, PipelinePipelineInterface::class, PipelinePipeline::class);
    $this->alterInfo('pipeline_pipeline_info');
    $this->setCacheBackend($cache_backend, 'pipeline_pipeline_plugins');
  }

}
