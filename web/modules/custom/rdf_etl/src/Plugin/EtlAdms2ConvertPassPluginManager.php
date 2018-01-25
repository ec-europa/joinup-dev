<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Plugin;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\rdf_etl\Annotation\Adms2ConvertPass;

/**
 * Provides the  ADMS v1 to v2 transformation plugin manager.
 */
class EtlAdms2ConvertPassPluginManager extends DefaultPluginManager {

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
    parent::__construct('Plugin/EtlAdms2ConvertPass', $namespaces, $module_handler, EtlAdms2ConvertPassInterface::class, Adms2ConvertPass::class);
    $this->alterInfo('rdf_etl_admis2_convert_pass_info');
    $this->setCacheBackend($cache_backend, 'rdf_etl_admis2_convert_pass_plugins');
  }

  /**
   * {@inheritdoc}
   */
  protected function findDefinitions() {
    $definitions = parent::findDefinitions();
    // We do the sort here, not in ::getDefinitions(), so that definitions are
    // cache correctly.
    uasort($definitions, [SortArray::class, 'sortByWeightElement']);
    return $definitions;
  }

}
