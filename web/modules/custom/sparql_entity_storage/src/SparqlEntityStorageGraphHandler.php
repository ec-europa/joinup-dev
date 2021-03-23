<?php

declare(strict_types = 1);

namespace Drupal\sparql_entity_storage;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\sparql_entity_storage\Entity\SparqlMapping;
use Drupal\sparql_entity_storage\Event\DefaultGraphsEvent;
use Drupal\sparql_entity_storage\Event\SparqlEntityStorageEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Contains helper methods for managing the SPARQL graphs.
 */
class SparqlEntityStorageGraphHandler implements SparqlEntityStorageGraphHandlerInterface {

  /**
   * Static cache.
   *
   * @var array
   */
  protected $cache = self::EMPTY_CACHE;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The SPARQL graph config entity storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $sparqlGraphStorage;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a SPARQL graph handler object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher) {
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function getGraphDefinitions(string $entity_type_id): array {
    if (!isset($this->cache['definition'][$entity_type_id])) {
      $query = $this->getSparqlGraphStorage()->getQuery();
      $ids = $query->condition($query->orConditionGroup()
        ->condition('entity_types.*', [$entity_type_id], 'IN')
        // A NULL value means "all entity types".
        ->notExists('entity_types')
      )->condition('status', TRUE)
        // A determined order is a key feature.
        ->sort('weight', 'ASC')
        ->execute();

      if (!$ids) {
        // Do not cache an empty set, it may occur because this runs before any
        // configuration has been imported, so the entities are not yet in.
        return [];
      }

      $graphs = $this->getSparqlGraphStorage()->loadMultiple($ids);

      $this->cache['definition'][$entity_type_id] = array_map(function (SparqlGraphInterface $graph): array {
        return [
          'title' => $graph->label(),
          'description' => $graph->getDescription(),
        ];
      }, $graphs);
    }
    return $this->cache['definition'][$entity_type_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeGraphIds(string $entity_type_id, array $limit_to_graph_ids = NULL): array {
    $graph_ids = array_keys($this->getGraphDefinitions($entity_type_id));
    if ($limit_to_graph_ids) {
      $graph_ids = array_intersect($graph_ids, $limit_to_graph_ids);
    }
    return $graph_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeDefaultGraphIds(string $entity_type_id): array {
    if (!isset($this->cache['default_graphs'][$entity_type_id])) {
      $entity_graph_ids = $this->getEntityTypeGraphIds($entity_type_id);
      /** @var \Drupal\sparql_entity_storage\Event\DefaultGraphsEvent $event */
      $event = $this->eventDispatcher->dispatch(
        SparqlEntityStorageEvents::DEFAULT_GRAPHS,
        new DefaultGraphsEvent($entity_type_id, $entity_graph_ids)
      );
      // Do not allow 3rd party code to add invalid or disabled graphs.
      $default_graph_ids = array_intersect($event->getDefaultGraphIds(), $entity_graph_ids);

      $this->cache['default_graphs'][$entity_type_id] = $default_graph_ids;
    }
    return $this->cache['default_graphs'][$entity_type_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultGraphId(string $entity_type_id): string {
    $graph_ids = $this->getEntityTypeGraphIds($entity_type_id);
    return reset($graph_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleGraphUri(string $entity_type_id, string $bundle, string $graph_id): ?string {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $bundle_key = ($entity_type->hasKey('bundle') && $entity_type->getBundleEntityType()) ? $bundle : $entity_type_id;
    return $this->getEntityTypeGraphUris($entity_type_id)[$bundle_key][$graph_id] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeGraphUris(string $entity_type_id, array $limit_to_graph_ids = NULL): array {
    if (!isset($this->cache['structure'][$entity_type_id])) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      if ($entity_type->hasKey('bundle') && ($bundle_entity_id = $entity_type->getBundleEntityType())) {
        $bundle_keys = array_values($this->entityTypeManager->getStorage($bundle_entity_id)->getQuery()->execute());
      }
      else {
        $bundle_keys = [$entity_type_id];
      }

      foreach ($bundle_keys as $bundle_key) {
        $graphs = ($mapping = SparqlMapping::loadByName($entity_type_id, $bundle_key)) ? $mapping->getGraphs() : [];
        $this->cache['structure'][$entity_type_id][$bundle_key] = $graphs;
      }
    }

    // Limit the results.
    if ($limit_to_graph_ids) {
      return array_map(function (array $graphs) use ($limit_to_graph_ids): array {
        return array_intersect_key($graphs, array_flip($limit_to_graph_ids));
      }, $this->cache['structure'][$entity_type_id]);
    }

    return $this->cache['structure'][$entity_type_id];
  }

  /**
   * {@inheritdoc}
   */
  public function bundleHasGraph(string $entity_type_id, string $bundle, string $graph_id): bool {
    $entity_type_graphs = $this->getEntityTypeGraphUris($entity_type_id);
    return !empty($entity_type_graphs[$bundle][$graph_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeGraphUrisFlatList(string $entity_type_id, array $limit_to_graph_ids = NULL): array {
    $graphs = $this->getEntityTypeGraphUris($entity_type_id, $limit_to_graph_ids);
    return array_reduce($graphs, function (array $uris, array $bundle_graphs): array {
      return array_merge($uris, array_flip($bundle_graphs));
    }, []);
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleGraphId(string $entity_type_id, string $bundle, string $graph_uri): ?string {
    $graphs = $this->getEntityTypeGraphUris($entity_type_id);
    $search = array_search($graph_uri, $graphs[$bundle]);
    return $search !== FALSE ? $search : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCache(array $path = NULL): void {
    if (empty($path)) {
      $this->cache = static::EMPTY_CACHE;
      return;
    }
    NestedArray::unsetValue($this->cache, $path);

    // If the path was a top-level cache category, restore its "empty version".
    if (count($path) === 1 && array_key_exists($path[0], static::EMPTY_CACHE)) {
      $this->cache[$path[0]] = static::EMPTY_CACHE[$path[0]];
    }
  }

  /**
   * Returns the SPARQL graph config entity storage service.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   *   The SPARQL graph config entity storage service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   If the entity type is not found.
   */
  protected function getSparqlGraphStorage(): ConfigEntityStorageInterface {
    if (!isset($this->sparqlGraphStorage)) {
      $this->sparqlGraphStorage = $this->entityTypeManager->getStorage('sparql_graph');
    }
    return $this->sparqlGraphStorage;
  }

}
