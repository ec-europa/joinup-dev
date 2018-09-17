<?php

declare(strict_types = 1);

namespace Drupal\search_api_field\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api_field\Annotation\SearchApiFieldFilter;

/**
 * Default implementation of filter plugin manager.
 */
class FilterPluginManager extends DefaultPluginManager implements FilterPluginManagerInterface {

  /**
   * The plugins grouped by their type.
   *
   * @var array
   */
  protected $pluginsByType;

  /**
   * Constructs a new filter plugin manager.
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
    parent::__construct('Plugin/SearchApiField/Filter', $namespaces, $module_handler, FilterPluginInterface::class, SearchApiFieldFilter::class);

    $this->alterInfo('search_api_field_filter_info');
    $this->setCacheBackend($cache_backend, 'search_api_field_filter_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitionsForField(FieldInterface $field): array {
    if (empty($this->pluginsByType)) {
      $this->pluginsByType = [
        'data_types' => [],
        'fields' => [],
      ];
      // Loop through all the plugin definitions and extract which fields or
      // data types are covered by which plugin.
      foreach ($this->getDefinitions() as $id => $definition) {
        foreach (['data_types', 'fields'] as $applies) {
          foreach ($definition[$applies] as $to) {
            if (!array_key_exists($to, $this->pluginsByType[$applies])) {
              $this->pluginsByType[$applies][$to] = [];
            }

            $this->pluginsByType[$applies][$to][] = $id;
          }
        }
      }
    }

    $plugins_ids = [];
    // Use field specific definitions if available.
    if (!empty($this->pluginsByType['fields'][$field->getFieldIdentifier()])) {
      $plugins_ids = $this->pluginsByType['fields'][$field->getFieldIdentifier()];
    }
    elseif (!empty($this->pluginsByType['data_types'][$field->getType()])) {
      $plugins_ids = $this->pluginsByType['data_types'][$field->getType()];
    }

    $definitions = [];
    foreach ($plugins_ids as $plugins_id) {
      $definitions[$plugins_id] = $this->getDefinition($plugins_id);
    }

    return $definitions;
  }

}
