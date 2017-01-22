<?php

namespace Drupal\rdf_entity;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\rdf_entity\Annotation\RdfEntityId;
use Drupal\rdf_entity\Entity\RdfEntitySparqlStorage;

/**
 * Plugin manager for entity ID generator plugins.
 */
class RdfEntityIdPluginManager extends DefaultPluginManager implements FallbackPluginManagerInterface {

  /**
   * Cached plugin instances.
   *
   * @var array
   */
  protected $instances = [];

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs the RdfEntityIdPluginManager object.
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
    parent::__construct('Plugin/rdf_entity/Id', $namespaces, $module_handler, RdfEntityIdPluginInterface::class, RdfEntityId::class);
    $this->alterInfo('rdf_taxonomy_tid_info');
    $this->setCacheBackend($cache_backend, 'rdf_taxonomy_tid_plugins');
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'fallback';
  }

  /**
   * Initializes the proper plugin given a RDF entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\rdf_entity\RdfEntityIdPluginInterface
   *   The plugin.
   */
  public function getPlugin(EntityInterface $entity) {
    $entity_type_id = $entity->getEntityTypeId();
    $bundle = $entity->bundle();

    if (!isset($this->instances[$entity_type_id][$bundle])) {
      $options = ['entity' => $entity, 'plugin_id' => NULL];
      foreach ($this->getDefinitions() as $plugin_id => $definition) {
        if (isset($definition['bundles'][$entity_type_id]) && in_array($bundle, $definition['bundles'][$entity_type_id])) {
          $options['plugin_id'] = $plugin_id;
          break;
        }
      }
      $this->instances[$entity_type_id][$bundle] = $this->getInstance($options);
    }

    return $this->instances[$entity_type_id][$bundle];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    return array_filter(parent::getDefinitions(), function (array $definition) {
      // Remove the fallback plugin from discovery.
      return $definition['id'] != $this->getFallbackPluginId($definition['id']);
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    if (empty($entity = $options['entity'])) {
      throw new \InvalidArgumentException("Options must contain the 'entity' object.");
    }

    $plugin_id = array_key_exists('plugin_id', $options) ? $options['plugin_id'] : NULL;
    $class = get_class($this->entityTypeManager->getStorage($entity->getEntityTypeId()));
    if ($class != RdfEntitySparqlStorage::class && !is_subclass_of($class, RdfEntitySparqlStorage::class)) {
      throw new \InvalidArgumentException("Passed entity must extend RdfEntitySparqlStorage.");
    }

    return $this->createInstance($plugin_id, ['entity' => $entity]);
  }

}
