<?php

namespace Drupal\search_api_arbitrary_facet\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Arbitrary facet plugin manager.
 */
class ArbitraryFacetManager extends DefaultPluginManager {

  /**
   * Constructs a new ArbitraryFacetManager object.
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
    parent::__construct('Plugin/ArbitraryFacet', $namespaces, $module_handler, 'Drupal\search_api_arbitrary_facet\Plugin\ArbitraryFacetInterface', 'Drupal\search_api_arbitrary_facet\Annotation\ArbitraryFacet');

    $this->alterInfo('search_api_arbitrary_facet_arbitrary_facet_info');
    $this->setCacheBackend($cache_backend, 'search_api_arbitrary_facet_arbitrary_facet_plugins');
  }

}
