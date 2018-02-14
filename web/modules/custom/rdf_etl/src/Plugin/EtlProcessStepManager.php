<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\rdf_etl\Annotation\EtlProcessStep;

/**
 * Provides the Process step plugin manager.
 */
class EtlProcessStepManager extends DefaultPluginManager {

  /**
   * Constructs a new EtlProcessStepManager object.
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
    parent::__construct('Plugin/EtlProcessStep', $namespaces, $module_handler, EtlProcessStepInterface::class, EtlProcessStep::class);
    $this->alterInfo('rdf_etl_etl_process_step_info');
    $this->setCacheBackend($cache_backend, 'rdf_etl_etl_process_step_plugins');
  }

}
