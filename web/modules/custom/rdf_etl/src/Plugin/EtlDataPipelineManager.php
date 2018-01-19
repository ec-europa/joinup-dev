<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Data pipeline plugin manager.
 */
class EtlDataPipelineManager extends DefaultPluginManager {

  /**
   * Constructs a new EtlDataPipelineManager object.
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
    parent::__construct('Plugin/EtlDataPipeline', $namespaces, $module_handler, 'Drupal\rdf_etl\Plugin\EtlDataPipelineInterface', 'Drupal\rdf_etl\Annotation\EtlDataPipeline');

    $this->alterInfo('rdf_etl_etl_data_pipeline_info');
    $this->setCacheBackend($cache_backend, 'rdf_etl_etl_data_pipeline_plugins');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   *   Plugin does not adhere to interface.
   */
  public function createInstance($plugin_id, array $configuration = []): EtlDataPipelineInterface {
    $data_pipeline = parent::createInstance($plugin_id, $configuration);
    if (!$data_pipeline instanceof EtlDataPipelineInterface) {
      throw new \Exception('Incorrect plugin: ' . $plugin_id);
    }
    return $data_pipeline;
  }

}
