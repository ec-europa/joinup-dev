<?php

declare(strict_types = 1);

namespace Drupal\workflow_state_permission;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin manager for workflow state permission plugins.
 */
class WorkflowStatePermissionPluginManager extends DefaultPluginManager {

  /**
   * Constructs an OgGroupResolverPluginManager service.
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
    parent::__construct('Plugin/WorkflowStatePermission', $namespaces, $module_handler, '\Drupal\workflow_state_permission\WorkflowStatePermissionPluginInterface', 'Drupal\workflow_state_permission\Annotation\WorkflowStatePermission');

    $this->alterInfo('workflow_state_permission_plugin_info');
    $this->setCacheBackend($cache_backend, 'workflow_state_permission_plugin');
  }

}
