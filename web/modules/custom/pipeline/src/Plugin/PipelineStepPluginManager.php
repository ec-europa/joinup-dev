<?php

namespace Drupal\pipeline\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\pipeline\Annotation\PipelineStep;

/**
 * Provides the pipeline step plugin manager.
 */
class PipelineStepPluginManager extends DefaultPluginManager {

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
    parent::__construct('Plugin/pipeline/Step', $namespaces, $module_handler, PipelineStepInterface::class, PipelineStep::class);
    $this->alterInfo('pipeline_step_info');
    $this->setCacheBackend($cache_backend, 'pipeline_step_plugins');
  }

}
