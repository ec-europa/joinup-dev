<?php

namespace Drupal\sparql_entity_storage;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\sparql_entity_storage\Annotation\SparqlEntityIdGenerator;
use Drupal\sparql_entity_storage\Entity\SparqlMapping;

/**
 * Plugin manager for entity ID generator plugins.
 */
class SparqlEntityStorageEntityIdPluginManager extends DefaultPluginManager implements FallbackPluginManagerInterface {

  /**
   * Cached plugin instances.
   *
   * @var \Drupal\sparql_entity_storage\SparqlEntityStorageEntityIdPluginInterface[][]
   */
  protected $instances = [];

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Builds a new plugin manager instance.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct('Plugin/sparql_entity_storage/Id', $namespaces, $module_handler, SparqlEntityStorageEntityIdPluginInterface::class, SparqlEntityIdGenerator::class);
    $this->alterInfo('sparql_entity_id_info');
    $this->setCacheBackend($cache_backend, 'sparql_entity_id_plugins');
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'default';
  }

  /**
   * Initializes the proper plugin given an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\sparql_entity_storage\SparqlEntityStorageEntityIdPluginInterface
   *   The plugin.
   */
  public function getPlugin(ContentEntityInterface $entity) {
    $entity_type_id = $entity->getEntityTypeId();
    $bundle_id = $entity->bundle();

    if (!isset($this->instances[$entity_type_id][$bundle_id])) {
      $options = ['plugin_id' => NULL];
      if ($mapping = SparqlMapping::loadByName($entity_type_id, $bundle_id)) {
        if ($plugin_id = $mapping->getEntityIdPlugin()) {
          $options['plugin_id'] = $plugin_id;
        }
      }
      $this->instances[$entity_type_id][$bundle_id] = $this->getInstance($options);
    }

    return $this->instances[$entity_type_id][$bundle_id]->setEntity($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    $plugin_id = array_key_exists('plugin_id', $options) ? $options['plugin_id'] : NULL;
    return $this->createInstance($plugin_id);
  }

}
