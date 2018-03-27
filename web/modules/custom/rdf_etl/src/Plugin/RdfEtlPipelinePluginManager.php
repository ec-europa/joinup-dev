<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\rdf_etl\Annotation\RdfEtlPipeline;

/**
 * Provides the Data pipeline plugin manager.
 */
class RdfEtlPipelinePluginManager extends DefaultPluginManager {

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
    parent::__construct('Plugin/rdf_etl/Pipeline', $namespaces, $module_handler, RdfEtlPipelineInterface::class, RdfEtlPipeline::class);
    $this->alterInfo('rdf_etl_pipeline_info');
    $this->setCacheBackend($cache_backend, 'rdf_etl_pipeline_plugins');
  }

}
