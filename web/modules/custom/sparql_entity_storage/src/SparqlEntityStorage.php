<?php

declare(strict_types = 1);

namespace Drupal\sparql_entity_storage;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\sparql_entity_storage\Driver\Database\sparql\ConnectionInterface;
use Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlArg;
use Drupal\sparql_entity_storage\Entity\SparqlGraph;
use Drupal\sparql_entity_storage\Exception\DuplicatedIdException;
use EasyRdf\Graph;
use EasyRdf\Literal;
use EasyRdf\Resource;
use EasyRdf\Sparql\Result;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a entity storage backend that uses a Sparql endpoint.
 */
class SparqlEntityStorage extends ContentEntityStorageBase implements SparqlEntityStorageInterface {

  /**
   * The statically cached entities.
   *
   * The parent property has been removed in Drupal 8.6.x and replaces with a
   * service (see https://www.drupal.org/project/drupal/issues/1596472). We add
   * here the variable to make it available for the storage.
   *
   * @var array
   *
   * @see https://www.drupal.org/project/drupal/issues/1596472
   */
  protected $entities = [];

  /**
   * Sparql database connection.
   *
   * @var \Drupal\sparql_entity_storage\Driver\Database\sparql\ConnectionInterface
   */
  protected $sparql;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The default bundle predicate.
   *
   * @var string[]
   */
  protected $bundlePredicate = ['http://www.w3.org/1999/02/22-rdf-syntax-ns#type'];

  /**
   * Predicate used for field item delta storage.
   *
   * @var string
   */
  protected $drupalFieldDeltaPredicate = 'http://drupal.org/ontology/field-item-delta';

  /**
   * The SPARQL graph helper service object.
   *
   * @var \Drupal\sparql_entity_storage\SparqlEntityStorageGraphHandlerInterface
   */
  protected $graphHandler;

  /**
   * The SPARQL field mapping service.
   *
   * @var \Drupal\sparql_entity_storage\SparqlEntityStorageFieldHandlerInterface
   */
  protected $fieldHandler;

  /**
   * The entity ID generator plugin manager.
   *
   * @var \Drupal\sparql_entity_storage\SparqlEntityStorageEntityIdPluginManager
   */
  protected $entityIdPluginManager;

  /**
   * Default language code.
   *
   * @var string
   */
  protected $defaultLangcode;

  /**
   * Initialize the storage backend.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type this storage is about.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to be used.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface|null $memory_cache
   *   The memory cache backend.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\sparql_entity_storage\Driver\Database\sparql\ConnectionInterface $sparql
   *   The connection object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\sparql_entity_storage\SparqlEntityStorageGraphHandlerInterface $sparql_graph_handler
   *   The sPARQL graph helper service.
   * @param \Drupal\sparql_entity_storage\SparqlEntityStorageFieldHandlerInterface $sparql_field_handler
   *   The SPARQL field mapping service.
   * @param \Drupal\sparql_entity_storage\SparqlEntityStorageEntityIdPluginManager $entity_id_plugin_manager
   *   The entity ID generator plugin manager.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityFieldManagerInterface $entity_field_manager,
    CacheBackendInterface $cache,
    MemoryCacheInterface $memory_cache,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    ConnectionInterface $sparql,
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface $language_manager,
    ModuleHandlerInterface $module_handler,
    SparqlEntityStorageGraphHandlerInterface $sparql_graph_handler,
    SparqlEntityStorageFieldHandlerInterface $sparql_field_handler,
    SparqlEntityStorageEntityIdPluginManager $entity_id_plugin_manager
  ) {
    parent::__construct($entity_type, $entity_field_manager, $cache, $memory_cache, $entity_type_bundle_info);
    $this->sparql = $sparql;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->graphHandler = $sparql_graph_handler;
    $this->fieldHandler = $sparql_field_handler;
    $this->entityIdPluginManager = $entity_id_plugin_manager;
    $this->defaultLangcode = $language_manager->getDefaultLanguage()->getId();
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): self {
    return new static(
      $entity_type,
      $container->get('entity_field.manager'),
      $container->get('cache.entity'),
      $container->get('entity.memory_cache'),
      $container->get('entity_type.bundle.info'),
      $container->get('sparql.endpoint'),
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
      $container->get('module_handler'),
      $container->get('sparql.graph_handler'),
      $container->get('sparql.field_handler'),
      $container->get('plugin.manager.sparql_entity_id')
    );
  }

  /**
   * Builds a new graph (list of triples).
   *
   * @param string $graph_uri
   *   The URI of the graph.
   *
   * @return \EasyRdf\Graph
   *   The EasyRdf graph object.
   */
  protected static function getGraph($graph_uri) {
    $graph = new Graph($graph_uri);
    return $graph;
  }

  /**
   * {@inheritdoc}
   */
  public function create(array $values = []): ContentEntityInterface {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = parent::create($values);
    // Ensure the default graph if no explicit graph has been set.
    if ($entity->get('graph')->isEmpty()) {
      $entity->set('graph', SparqlGraph::DEFAULT);
    }
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundlePredicates(): array {
    return $this->bundlePredicate;
  }

  /**
   * {@inheritdoc}
   */
  public function getGraphHandler(): SparqlEntityStorageGraphHandlerInterface {
    return $this->graphHandler;
  }

  /**
   * {@inheritdoc}
   */
  public function getGraphDefinitions(): array {
    return $this->getGraphHandler()->getGraphDefinitions($this->entityTypeId);
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultiple(array $ids = NULL, array $graph_ids = []) {
    // Attempt to load entities from the persistent cache. This will remove IDs
    // that were loaded from $ids.
    $entities_from_cache = $this->getFromPersistentCache($ids, $graph_ids);
    // Load any remaining entities from the database.
    $entities_from_storage = $this->getFromStorage($ids, $graph_ids);

    return $entities_from_cache + $entities_from_storage;
  }

  /**
   * Gets entities from the storage.
   *
   * @param array|null $ids
   *   If not empty, return entities that match these IDs. Return all entities
   *   when NULL.
   * @param array $graph_ids
   *   A list of graph IDs.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   Array of entities from the storage.
   *
   * @throws \Drupal\sparql_entity_storage\Exception\SparqlQueryException
   *   If the SPARQL query fails.
   * @throws \Exception
   *   The query fails with no specific reason.
   */
  protected function getFromStorage(array $ids = NULL, array $graph_ids = []): array {
    $entities = [];
    while ($ids) {
      $ids_to_process = array_splice($ids, 0, 50);
      if ($entities_values = $this->loadFromStorage($ids_to_process, $graph_ids)) {
        foreach ($entities_values as $id => $entity_values) {
          $bundle = $this->bundleKey ? $entity_values[$this->bundleKey][LanguageInterface::LANGCODE_DEFAULT][0]['target_id'] : FALSE;
          $langcode_key = $this->getEntityType()->getKey('langcode');
          $translations = [];
          if (!empty($entity_values[$langcode_key])) {
            foreach ($entity_values[$langcode_key] as $data) {
              $langcode = reset($data)['value'] ?? NULL;
              if ($langcode) {
                $translations[] = $langcode;
              }
            }
          }
          $entity = new $this->entityClass($entity_values, $this->entityTypeId, $bundle, $translations);
          $this->trackOriginalGraph($entity);
          $entities[$id] = $entity;
        }
        $this->invokeStorageLoadHook($entities);
        $this->setPersistentCache($entities);
      }
    }
    return $entities;
  }

  /**
   * Retrieves the entity data from the SPARQL endpoint.
   *
   * @param string[] $ids
   *   A list of entity IDs.
   * @param string[]|null $graph_ids
   *   An ordered list of candidate graph IDs.
   *
   * @return array|null
   *   The entity values indexed by the field mapping ID or NULL in there are no
   *   results.
   *
   * @throws \Drupal\sparql_entity_storage\Exception\SparqlQueryException
   *   If the SPARQL query fails.
   * @throws \Exception
   *   The query fails with no specific reason.
   */
  protected function loadFromStorage(array $ids, array $graph_ids): ?array {
    if (empty($ids)) {
      return [];
    }

    $ids_string = SparqlArg::serializeUris($ids, ' ');
    $graphs = $this->getGraphHandler()->getEntityTypeGraphUrisFlatList($this->getEntityTypeId(), $graph_ids);

    $named_graph = '';
    foreach (array_keys($graphs) as $graph_id) {
      $named_graph .= '  FROM NAMED ' . SparqlArg::uri($graph_id) . "\n";
    }

    $query = <<<QUERY
SELECT ?graph ?id ?field ?value ?field1 ?value1
{$named_graph}
WHERE {
  GRAPH ?graph {
    VALUES ?id { {$ids_string} } .
    ?id ?field ?value .
    OPTIONAL {
      ?value ?field1 ?value1 .
      FILTER ( isBlank(?value) ) .
    }
  }
}
QUERY;

    $results = $this->sparql->query($query);
    return $this->processResults($results->getArrayCopy(), $graph_ids, $graphs);
  }

  /**
   * Transforms the results from SPARQL into entity/field API suitable values.
   *
   * @param array $triples
   *   The SPARQL query results as array.
   * @param string[] $graph_ids
   *   The graph priority list.
   * @param string[] $graphs
   *   An associative array of graph IDs, keyed by graph URI.
   *
   * @return array
   *   A complex associative array, ready to be passed to a content entity
   *   constructor.
   *
   * @throws \Exception
   *   When is not possible to get a single bundle ID from the bundle predicate.
   */
  protected function processResults(array $triples, array $graph_ids, array $graphs): array {
    $entity_type_id = $this->getEntityTypeId();
    $id_key = $this->getEntityType()->getKey('id');
    $bundle_key = $this->getEntityType()->getKey('bundle') ?: $entity_type_id;

    // Build values for triples having field predicates.
    $entities_per_graph = $this->buildComplexFields($triples, $graphs);
    // Append values for triples with no field level predicate.
    foreach ($triples as $triple) {
      if (!$this->isBlankNode($triple->value)) {
        $graph_id = $graphs[$triple->graph->getUri()];
        $id = $triple->id->getUri();
        $predicate = $triple->field->getUri();
        $langcode = $this->getLangcode($triple->value);
        $entities_per_graph[$graph_id][$id][$predicate][$langcode][] = (string) $triple->value;
      }
    }

    // Sort by graph priority (the associative array key).
    $graphs_in_use = array_intersect($graph_ids, array_keys($entities_per_graph));
    $entities_per_graph = array_merge(array_flip($graphs_in_use), $entities_per_graph);

    // Remove entities already present in a higher priority graph.
    array_walk($entities_per_graph, function (array &$entities, string $graph_id) use ($entity_type_id, $id_key, $bundle_key): void {
      static $processed_entities = [];

      // Keep only the first appearance of entity as the array is now sorted.
      $entities = array_diff_key($entities, array_flip($processed_entities));
      $processed_entities = array_merge($processed_entities, array_keys($entities));

      // Iterate over entities to build their fields.
      array_walk($entities, function (array &$entity, string $id) use ($entity_type_id, $id_key, $bundle_key, $graph_id): void {
        if (!$bundle = $this->getActiveBundle($entity)) {
          // Cannot detect a bundle out of this resource. Most probably it's a
          // set of arbitrary triples, not under entity/field API control.
          unset($entity);
          return;
        }
        $entity[$bundle_key][LanguageInterface::LANGCODE_DEFAULT][0]['target_id'] = $bundle;
        $entity[$id_key][LanguageInterface::LANGCODE_DEFAULT][0]['value'] = $id;
        $entity['graph'][LanguageInterface::LANGCODE_DEFAULT][0]['target_id'] = $graph_id;
        $processed_fields = [$id_key, $bundle_key, 'graph'];

        foreach (array_diff_key($entity, array_flip($processed_fields)) as $predicate => $languages) {
          // Complex field with field predicate.
          if ($field_name = $this->fieldHandler->getFieldNameByPredicate($entity_type_id, $bundle, $predicate)) {
            foreach ($languages as $langcode => $values) {
              foreach ($values as $delta => $item) {
                foreach ($item as $column_predicate => $value) {
                  $column_name = $this->fieldHandler->getColumnNameByPredicate($entity_type_id, $bundle, $column_predicate);
                  $entity[$field_name][$langcode][$delta][$column_name] = $this->fieldHandler->getInboundValue($entity_type_id, $field_name, $value, $langcode, $column_name, $bundle);
                }
              }
            }
          }
          // Simple column mappings.
          elseif ($column_name = $this->fieldHandler->getColumnNameByPredicate($entity_type_id, $bundle, $predicate)) {
            $field_name = $this->fieldHandler->getColumnFieldNameByPredicate($entity_type_id, $bundle, $predicate);
            foreach ($languages as $langcode => $values) {
              foreach ($values as $delta => $value) {
                $entity[$field_name][$langcode][$delta][$column_name] = $this->fieldHandler->getInboundValue($entity_type_id, $field_name, $value, $langcode, $column_name, $bundle);
              }
            }
          }

          // Remove already processed fields but also the arbitrary values, not
          // covered by the Drupal entity/field API.
          unset($entity[$predicate]);
        }
      });
    });

    // Flatten the array by removing the graph layer.
    return array_reduce($entities_per_graph, function (array $entities, array $graph_entities): array {
      return $entities + $graph_entities;
    }, []);
  }

  /**
   * Returns a list of entity values corresponding to fields with predicate.
   *
   * This produces a result such as:
   *
   * @codingStandardsIgnoreStart
   * graph_id:
   *   http://entity/id/1:
   *     http://field/foo:
   *       x-default:
   *         0:
   *           http://field/foo/column/bar: '1st value'
   *           http://field/foo/column/baz: 'http://exmple.com/1'
   *         1:
   *           http://field/foo/column/bar: '2nd value'
   *           http://field/foo/column/baz: 'http://exmple.com/2'
   *         2:
   *           ...
   *         ...
   *       de:
   *         ...
   *     http://other_field:
   *       ...
   *   http://other_entity_id:
   *     ...
   * other_graph:
   *   ...
   * @codingStandardsIgnoreEnd
   *
   * @param array $triples
   *   The list of triples as an array. Each item is an object.
   * @param array $graphs
   *   The list of graph IDs keyed by graph URI.
   *
   * @return array
   *   A list of entity values keyed by graph ID.
   */
  protected function buildComplexFields(array $triples, array $graphs): array {
    $entities_per_graph = array_reduce($triples, function (array $entities_per_graph, \stdClass $triple) use ($graphs): array {
      if ($this->isBlankNode($triple->value)) {
        $graph_id = $graphs[$triple->graph->getUri()];
        $id = $triple->id->getUri();
        $field_predicate = $triple->field->getUri();
        $langcode = $this->getLangcode($triple->value1);
        // We're using 'weight', not 'delta', just to be able to sort later by
        // taking advantage of SortArray::sortByWeightElement().
        // @see \Drupal\Component\Utility\SortArray::sortByWeightElement()
        $column = $triple->field1->getUri() === $this->drupalFieldDeltaPredicate ? 'weight' : $triple->field1->getUri();
        $bnode_uri = $triple->value->getUri();
        $entities_per_graph[$graph_id][$id][$field_predicate][$langcode][$bnode_uri][$column] = (string) $triple->value1;
      }
      return $entities_per_graph;
    }, []);

    // Sort by deltas and cleanup.
    foreach ($entities_per_graph as &$entities) {
      foreach ($entities as &$entity) {
        foreach ($entity as &$languages) {
          foreach ($languages as &$items) {
            // Sort by 'weight' to honour the delta.
            uasort($items, [SortArray::class, 'sortByWeightElement']);
            // Remove the blank node URI keys.
            $items = array_values($items);
            // Remove the 'weight' now, we're already sorted.
            array_walk($items, function (array &$item): void {
              unset($item['weight']);
            });
          }
        }
      }
    }

    return $entities_per_graph;
  }

  /**
   * Returns the language code for a given field column passed as triple object.
   *
   * @param mixed $value
   *   The triple object.
   *
   * @return string
   *   The the language code.
   */
  protected function getLangcode($value): string {
    return (
      $value instanceof Literal
      && ($langcode = $value->getLang())
      && $langcode !== $this->defaultLangcode
    ) ? $langcode : LanguageInterface::LANGCODE_DEFAULT;
  }

  /**
   * Checks if a given triple item is a blank node.
   *
   * @param mixed $value
   *   A triple item, either a subject or an object.
   *
   * @return bool
   *   If the passed triple item is a blank node.
   */
  protected function isBlankNode($value): bool {
    return $value instanceof Resource && $value->isBNode();
  }

  /**
   * Derives the bundle from the rdf:type.
   *
   * @param array $entity_values
   *   Entity in a raw formatted array. Matched values are removed from the
   *   entity values array.
   *
   * @return string
   *   The bundle ID string.
   *
   * @throws \Exception
   *    Thrown when the bundle is not found.
   */
  protected function getActiveBundle(array &$entity_values): ?string {
    $bundles = [];
    foreach ($this->bundlePredicate as $bundle_predicate) {
      if (isset($entity_values[$bundle_predicate])) {
        $bundle_data = $entity_values[$bundle_predicate];
        $bundles += $this->fieldHandler->getInboundBundleValue($this->entityTypeId, $bundle_data[LanguageInterface::LANGCODE_DEFAULT][0]);
        unset($entity_values[$bundle_predicate]);
      }
    }
    if (empty($bundles)) {
      return NULL;
    }

    // Since it is possible to map more than one bundles to the same uri, allow
    // modules to handle this.
    $this->moduleHandler->alter('sparql_bundle_load', $entity_values, $bundles);
    if (count($bundles) > 1) {
      throw new \Exception('More than one bundles are defined for this URI.');
    }
    return reset($bundles);
  }

  /**
   * {@inheritdoc}
   */
  public function load($id, array $graph_ids = NULL): ?ContentEntityInterface {
    $entities = $this->loadMultiple([$id], $graph_ids);
    return array_shift($entities);
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = NULL, array $graph_ids = NULL): array {
    $this->checkGraphs($graph_ids);

    // We copy this part from parent::loadMultiple(), otherwise we cannot pass
    // the $graph_ids to self::getFromStaticCache() and self::doLoadMultiple().
    // START parent::loadMultiple() fork.
    $entities = [];
    $passed_ids = !empty($ids) ? array_flip($ids) : FALSE;
    if ($this->entityType->isStaticallyCacheable() && $ids) {
      $entities += $this->getFromStaticCache($ids, $graph_ids);
      if ($passed_ids) {
        $ids = array_keys(array_diff_key($passed_ids, $entities));
      }
    }
    if ($ids === NULL || $ids) {
      $queried_entities = $this->doLoadMultiple($ids, $graph_ids);
    }
    if (!empty($queried_entities)) {
      $this->postLoad($queried_entities);
      $entities += $queried_entities;
    }
    if ($this->entityType->isStaticallyCacheable()) {
      if (!empty($queried_entities)) {
        $this->setStaticCache($queried_entities);
      }
    }
    if ($passed_ids) {
      $passed_ids = array_intersect_key($passed_ids, $entities);
      foreach ($entities as $entity) {
        $passed_ids[$entity->id()] = $entity;
      }
      $entities = $passed_ids;
    }
    // END parent::loadMultiple() fork.
    if (empty($entities)) {
      return [];
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  protected function doPreSave(EntityInterface $entity) {
    // The code bellow is forked from EntityStorageBase::doPreSave() and
    // ContentEntityStorageBase::doPreSave(). We are not using the original
    // methods in order to be able to pass an additional list of graphs
    // parameter to ::loadUnchanged() method.
    // START forking from ContentEntityStorageBase::doPreSave().
    /** @var \Drupal\Core\Entity\ContentEntityBase $entity */
    $entity->updateOriginalValues();
    if ($entity->getEntityType()->isRevisionable() && !$entity->isNew() && empty($entity->getLoadedRevisionId())) {
      $entity->updateLoadedRevisionId();
    }

    // START forking from EntityStorageBase::doPreSave().
    $id = $entity->id();
    if ($entity->getOriginalId() !== NULL) {
      $id = $entity->getOriginalId();
    }
    $id_exists = $this->has($id, $entity);
    if ($id_exists && $entity->isNew()) {
      throw new EntityStorageException("'{$this->entityTypeId}' entity with ID '$id' already exists.");
    }
    if ($id_exists && !isset($entity->original)) {
      // In the case when the entity graph has been changed before saving, we
      // need the original graph, so that we load the original/unchanged entity
      // from the backend. This property was set in during entity load, in
      // ::trackOriginalGraph(). We can rely on this property also when the
      // entity us saved via UI, as this value persists in entity over an entity
      // form submit, because the entity is stored in the form state.
      // @see \Drupal\sparql_entity_storage\SparqlEntityStorage::trackOriginalGraph()
      $entity->original = $this->loadUnchanged($id, [$entity->sparqlEntityOriginalGraph]);
    }
    $entity->preSave($this);
    $this->invokeHook('presave', $entity);
    // END forking from EntityStorageBase::doPreSave().
    if (!$entity->isNew()) {
      if (empty($entity->original) || $entity->id() != $entity->original->id()) {
        throw new EntityStorageException("Update existing '{$this->entityTypeId}' entity while changing the ID is not supported.");
      }
      if (!$entity->isNewRevision() && $entity->getRevisionId() != $entity->getLoadedRevisionId()) {
        throw new EntityStorageException("Update existing '{$this->entityTypeId}' entity revision while changing the revision ID is not supported.");
      }
    }
    // END forking from ContentEntityStorageBase::doPreSave().
    // Finally reset the entity original graph property so that that its updated
    // value is available for the rest of this request.
    $this->trackOriginalGraph($entity);

    return $id;
  }

  /**
   * {@inheritdoc}
   */
  public function loadUnchanged($id, array $graph_ids = NULL): ?ContentEntityInterface {
    $this->checkGraphs($graph_ids);

    // START: Code forked from parent::loadUnchanged() and adapted to accept
    // graph andidates.
    $ids = [$id];
    parent::resetCache($ids);

    // START: Code adapted from EntityStorageBase::resetCache().
    // This part is replacing the ContentEntityStorageBase::resetCache() line.
    if ($this->entityType->isStaticallyCacheable()) {
      foreach ($graph_ids as $graph_id) {
        unset($this->entities[$id][$graph_id]);
      }
    }
    // END: Code adapted from EntityStorageBase::resetCache().
    $entities = $this->getFromPersistentCache($ids, $graph_ids);
    if (!$entities) {
      $entities[$id] = $this->load($id, $graph_ids);
    }
    else {
      $this->postLoad($entities);
      if ($this->entityType->isStaticallyCacheable()) {
        $this->setStaticCache($entities);
      }
    }

    return $entities[$id];
    // END: Code forked from parent::loadUnchanged().
  }

  /**
   * {@inheritdoc}
   */
  public function loadRevision($revision_id) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRevision($revision_id) {
  }

  /**
   * {@inheritdoc}
   */
  public function deleteFromGraph(array $entities, string $graph_id): void {
    if (!empty($entities)) {
      $ids = array_map(function (ContentEntityInterface $entity): string {
        return $entity->id();
      }, $entities);
      // Make sure that passed entities are keyed by entity ID and are loaded
      // only from the requested graph.
      $entities = $this->loadMultiple($ids, [$graph_id]);
      $this->doDelete($entities);
      $this->resetCache(array_keys($entities));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasGraph(EntityInterface $entity, string $graph_id): bool {
    $graph_uri = $this->getGraphHandler()->getBundleGraphUri($entity->getEntityTypeId(), $entity->bundle(), $graph_id);
    return $this->idExists($entity->id(), $graph_uri);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByProperties(array $values = [], array $graph_ids = NULL): array {
    $this->checkGraphs($graph_ids);

    /** @var \Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlQueryInterface $query */
    $query = $this->getQuery()
      ->graphs($graph_ids)
      ->accessCheck(FALSE);
    $this->buildPropertyQuery($query, $values);
    $result = $query->execute();

    return $result ? $this->loadMultiple($result, $graph_ids) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $entities) {
    if (!$entities) {
      // If no entities were passed, do nothing.
      return;
    }

    // Ensure that the entities are keyed by ID.
    $keyed_entities = [];
    foreach ($entities as $entity) {
      $keyed_entities[$entity->id()] = $entity;
    }

    // Allow code to run before deleting.
    $entity_class = $this->entityClass;
    $entity_class::preDelete($this, $keyed_entities);
    foreach ($keyed_entities as $entity) {
      $this->invokeHook('predelete', $entity);
    }
    $entities_by_graph = [];
    /** @var \Drupal\Core\Entity\EntityInterface $keyed_entity */
    foreach ($keyed_entities as $keyed_entity) {
      // Determine all possible graphs for the entity.
      $graphs_by_bundle = $this->getGraphHandler()->getEntityTypeGraphUris($this->getEntityTypeId());
      $graphs = $graphs_by_bundle[$keyed_entity->bundle()];
      foreach ($graphs as $graph_uri) {
        $entities_by_graph[$graph_uri][$keyed_entity->id()] = $keyed_entity;
      }
    }
    /** @var string $id */
    foreach ($entities_by_graph as $graph => $entities_to_delete) {
      $this->doDeleteFromGraph($entities_to_delete, $graph);
    }
    $this->resetCache(array_keys($keyed_entities), array_keys($graphs));

    // Allow code to run after deleting.
    $entity_class::postDelete($this, $keyed_entities);
    foreach ($keyed_entities as $entity) {
      $this->invokeHook('delete', $entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doDelete($entities) {
    $entities_by_graph = [];
    /** @var string $id */
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    foreach ($entities as $id => $entity) {
      $graph_uri = $this->getGraphHandler()->getBundleGraphUri($entity->getEntityTypeId(), $entity->bundle(), (string) $entity->get('graph')->target_id);
      $entities_by_graph[$graph_uri][$id] = $entity;
    }
    foreach ($entities_by_graph as $graph_uri => $entities_to_delete) {
      $this->doDeleteFromGraph($entities, $graph_uri);
    }
  }

  /**
   * Deletes triples corresponding to the given entities from a given graph.
   *
   * Only delete the triples that are controlled by the entity/field API, by
   * filtering on field predicates. Additional data that might be imported
   * through an external repository are not lost during entity deletion.
   *
   * @param array $entities
   *   An array of entity objects to delete.
   * @param string $graph_uri
   *   The graph URI to delete from.
   *
   * @throws \Exception
   *   The query fails with no specific reason.
   */
  protected function doDeleteFromGraph(array $entities, string $graph_uri): void {
    $field_columns_predicates = $this->fieldHandler->getPropertyListToArray($this->getEntityTypeId());
    $field_predicates = $this->fieldHandler->getAllFieldPredicates($this->getEntityTypeId());
    $all_predicates = SparqlArg::serializeUris(array_values(array_merge($field_predicates, $field_columns_predicates)));
    // Add the field delta predicate.
    $field_columns_predicates[] = $this->drupalFieldDeltaPredicate;
    $field_columns_predicates = SparqlArg::serializeUris($field_columns_predicates);

    $graph_uri = SparqlArg::uri($graph_uri);
    $ids = SparqlArg::serializeUris(array_keys($entities), ' ');

    $query = <<<QUERY
WITH {$graph_uri}
DELETE {
  ?id ?field ?value .
  ?value ?field1 ?value1 .
}
WHERE {
  VALUES ?id { {$ids} } .
  ?entity_id ?field ?value .
  OPTIONAL {
    ?value ?field1 ?value1 .
    FILTER ( isBlank(?value) ) .
    FILTER ( ?field1 IN( {$field_columns_predicates} ) ) .
  }
  FILTER ( ?field IN( {$all_predicates} ) ) .
}
QUERY;
    $this->sparql->query($query);
  }

  /**
   * {@inheritdoc}
   */
  protected function getQueryServiceName() {
    return 'entity.query.sparql';
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadRevisionFieldItems($revision_id) {
  }

  /**
   * {@inheritdoc}
   */
  protected function doSaveFieldItems(ContentEntityInterface $entity, array $names = []) {
  }

  /**
   * {@inheritdoc}
   */
  protected function doDeleteFieldItems($entities) {
  }

  /**
   * {@inheritdoc}
   */
  protected function doDeleteRevisionFieldItems(ContentEntityInterface $revision) {
  }

  /**
   * {@inheritdoc}
   */
  protected function readFieldItemsToPurge(FieldDefinitionInterface $field_definition, $batch_size) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function purgeFieldItems(ContentEntityInterface $entity, FieldDefinitionInterface $field_definition) {
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave($id, EntityInterface $entity) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity_type_id = $this->getEntityTypeId();
    $bundle = $entity->bundle();
    // Generate an ID before saving, if none is available. If the ID generation
    // occurs earlier in the process (like on EntityInterface::create()), the
    // entity might be considered not new by modules that don't strictly use the
    // EntityInterface::isNew() method.
    if (empty($id)) {
      $id = $this->entityIdPluginManager->getPlugin($entity)->generate();
      $entity->{$this->idKey} = $id;
    }
    elseif ($entity->isNew() && $this->idExists($id)) {
      throw new DuplicatedIdException("Attempting to create a new entity with the ID '$id' already taken.");
    }

    // If the graph is not specified, fallback to the default one for the entity
    // type.
    if ($entity->get('graph')->isEmpty()) {
      $entity->set('graph', $this->getGraphHandler()->getDefaultGraphId($entity_type_id));
    }

    $graph_id = $entity->get('graph')->target_id;
    $graph_uri = $this->getGraphHandler()->getBundleGraphUri($entity_type_id, $bundle, $graph_id);
    $graph = self::getGraph($graph_uri);
    $lang_array = $this->toLangArray($entity);
    foreach ($lang_array as $field_name => $langcode_data) {
      $field_predicate = $this->fieldHandler->getFieldPredicate($entity_type_id, $field_name);
      $cardinality = $this->fieldHandler->getFieldCardinality($entity_type_id, $field_name);
      foreach ($langcode_data as $langcode => $field_item_list) {
        foreach ($field_item_list as $delta => $field_item) {
          // This is a multi-value field, we store the subsequent field item
          // columns in a blank node.
          if ($field_predicate) {
            $bnode_id = "_:{$field_name}__{$delta}";
            $graph->add($id, $field_predicate, [
              'value' => $bnode_id,
              'type' => 'bnode',
            ]);
            // Field item delta.
            $graph->add($bnode_id, $this->drupalFieldDeltaPredicate, $delta);
          }
          foreach ($field_item as $column => $value) {
            // Filter out empty values or non mapped fields. The ID is also
            // excluded as it is not mapped.
            if ($value === NULL || $value === '' || !$this->fieldHandler->hasFieldPredicate($entity_type_id, $bundle, $field_name, $column)) {
              continue;
            }
            $predicate = $this->fieldHandler->getFieldColumnPredicates($entity_type_id, $field_name, $column, $bundle);
            $predicate = reset($predicate);
            $value = $this->fieldHandler->getOutboundValue($entity_type_id, $field_name, $value, $langcode, $column, $bundle);
            if ($field_predicate) {
              $graph->add($bnode_id, $predicate, $value);
            }
            else {
              $graph->add($id, $predicate, $value);
            }
          }

          if ($cardinality !== FieldStorageConfigInterface::CARDINALITY_UNLIMITED && $cardinality === $delta + 1) {
            // Just reached the max delta for fields with limited cardinality.
            break;
          }
        }
      }
    }

    // Give implementations a chance to alter the graph right before is saved.
    $this->alterGraph($graph, $entity);

    if (!$entity->isNew()) {
      $this->doDeleteFromGraph([$entity->id() => $entity], $graph_uri);
    }
    try {
      $this->insert($graph, $graph_uri);
      return $entity->isNew() ? SAVED_NEW : SAVED_UPDATED;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doPostSave(EntityInterface $entity, $update) {
    parent::doPostSave($entity, $update);

    // After saving, this is now the "original entity", but subsequent saves
    // must be able to reference the original graph.
    // @see \Drupal\Core\Entity\EntityStorageBase::doPostSave()
    $this->trackOriginalGraph($entity);
  }

  /**
   * In this method the latest values have to be applied to the entity.
   *
   * The end array should have an index with the x-default language which should
   * be the default language to save and one index for each other translation.
   *
   * Since the user can be presented with non translatable fields in the
   * translation form, the process has to give priority to the values of the
   * current language over the default language.
   *
   * So, the process is:
   * - If the current language is the default one, add all fields to the
   *   x-default index.
   * - If the current language is not the default language, then the default
   * - language will only provide the translatable fields as default and the
   *   non-translatable will be filled by the current language.
   * - All the other languages, will only provide the translatable fields.
   *
   * Only t_literal fields should be translatable.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to convert to an array of values.
   *
   * @return array
   *   The array of values including the translations.
   */
  protected function toLangArray(ContentEntityInterface $entity): array {
    $values = [];
    $languages = array_keys(array_filter($entity->getTranslationLanguages(), function (LanguageInterface $language) {
      return !$language->isLocked();
    }));
    $translatable_fields = array_keys($entity->getTranslatableFields());
    $fields = array_keys($entity->getFields());
    $non_translatable_fields = array_diff($fields, $translatable_fields);

    $current_langcode = $entity->language()->getId();
    if ($entity->isDefaultTranslation()) {
      foreach ($entity->getFields(FALSE) as $name => $field_item_list) {
        if (!$field_item_list->isEmpty()) {
          $values[$name][$current_langcode] = $field_item_list->getValue();
        }
      }
      $processed = [$entity->language()->getId()];
    }
    else {
      // Fill in the translatable fields of the default language and then all
      // the fields from the current language.
      $default_translation = $entity->getUntranslated();
      $default_langcode = $default_translation->language()->getId();
      foreach ($translatable_fields as $name) {
        $values[$name][$default_langcode] = $default_translation->get($name)->getValue();
      }
      // For the current language, add the translatable fields as a translation
      // and the non translatable fields as default.
      foreach ($non_translatable_fields as $name) {
        $values[$name][$default_langcode] = $entity->get($name)->getValue();
      }
      // The current language is not included in the translations if it is a
      // new translation and is outdated if it is not a new translation.
      // Thus, the handling occurs here, instead of the generic handling below.
      foreach ($translatable_fields as $name) {
        $values[$name][$current_langcode] = $entity->get($name)->getValue();
      }

      $processed = [$current_langcode, $default_langcode];
    }

    // For the rest of the languages not computed above, simply add the
    // the translatable fields. This will prevent data loss from the database.
    foreach (array_diff($languages, $processed) as $langcode) {
      if (!$entity->hasTranslation($langcode)) {
        continue;
      }
      $translation = $entity->getTranslation($langcode);
      foreach ($translatable_fields as $name) {
        $item_list = $translation->get($name);
        if (!$item_list->isEmpty()) {
          $values[$name][$langcode] = $item_list->getValue();
        }
      }
    }
    return $values;
  }

  /**
   * Alters the graph before saving the entity.
   *
   * Implementations are able to change, delete or add items to the graph before
   * this is saved to SPARQL backend.
   *
   * @param \EasyRdf\Graph $graph
   *   The graph to be altered.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being saved.
   */
  protected function alterGraph(Graph &$graph, EntityInterface $entity): void {}

  /**
   * Insert a graph of triples.
   *
   * @param \EasyRdf\Graph $graph
   *   The graph to insert.
   * @param string $graph_uri
   *   Graph to save to.
   *
   * @return \EasyRdf\Sparql\Result
   *   Response.
   *
   * @throws \Drupal\sparql_entity_storage\Exception\SparqlQueryException
   *   If the SPARQL query fails.
   * @throws \Exception
   *   The query fails with no specific reason.
   */
  protected function insert(Graph $graph, string $graph_uri): Result {
    $graph_uri = SparqlArg::uri($graph_uri);
    $query = "INSERT INTO $graph_uri {\n";
    $query .= $graph->serialise('ntriples') . "\n";
    $query .= '}';
    return $this->sparql->update($query);
  }

  /**
   * {@inheritdoc}
   */
  protected function has($id, EntityInterface $entity) {
    return !$entity->isNew();
  }

  /**
   * {@inheritdoc}
   */
  public function countFieldData($storage_definition, $as_bool = FALSE) {
    return $as_bool ? FALSE : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function hasData() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getFromStaticCache(array $ids, array $graph_ids = []) {
    $entities = [];
    foreach ($ids as $id) {
      // If there are more than one graphs in the request, return only the first
      // one, if exists. If the first candidate doesn't exist in the static
      // cache, we don't pickup the following because the first might be
      // available later in the persistent cache or in the storage.
      if (isset($this->entities[$id][$graph_ids[0]])) {
        if (!isset($entities[$id])) {
          $entities[$id] = $this->entities[$id][$graph_ids[0]];
        }
      }
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  protected function setStaticCache(array $entities) {
    if ($this->entityType->isStaticallyCacheable()) {
      foreach ($entities as $id => $entity) {
        $this->entities[$id][$entity->get('graph')->target_id] = $entity;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getFromPersistentCache(array &$ids = NULL, array $graph_ids = []) {
    if (!$this->entityType->isPersistentlyCacheable() || empty($ids)) {
      return [];
    }
    $entities = [];
    // Build the list of cache entries to retrieve.
    $cid_map = [];
    foreach ($ids as $id) {
      $graph_id = reset($graph_ids);
      $cid_map[$id] = "{$this->buildCacheId($id)}:{$graph_id}";
    }
    $cids = array_values($cid_map);
    if ($cache = $this->cacheBackend->getMultiple($cids)) {
      // Get the entities that were found in the cache.
      foreach ($ids as $index => $id) {
        $cid = $cid_map[$id];
        if (isset($cache[$cid]) && !isset($entities[$id])) {
          $entities[$id] = $cache[$cid]->data;
          unset($ids[$index]);
        }
      }
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  protected function setPersistentCache($entities) {
    if (!$this->entityType->isPersistentlyCacheable()) {
      return;
    }

    $cache_tags = [
      $this->entityTypeId . '_values',
      'entity_field_info',
    ];
    foreach ($entities as $id => $entity) {
      $cid = "{$this->buildCacheId($id)}:{$entity->graph->target_id}";
      $this->cacheBackend->set($cid, $entity, CacheBackendInterface::CACHE_PERMANENT, $cache_tags);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $ids = NULL, array $graph_ids = NULL): void {
    if ($graph_ids && !$ids) {
      throw new \InvalidArgumentException('Passing a value in $graphs_ids works only when used with non-null $ids.');
    }

    $this->checkGraphs($graph_ids, TRUE);

    if ($ids) {
      $cids = [];
      foreach ($ids as $id) {
        foreach ($graph_ids as $graph) {
          unset($this->entities[$id][$graph]);
          $cids[] = "{$this->buildCacheId($id)}:{$graph}";
        }
      }
      if ($this->entityType->isPersistentlyCacheable()) {
        $this->cacheBackend->deleteMultiple($cids);
      }
    }
    else {
      $this->entities = [];
      if ($this->entityType->isPersistentlyCacheable()) {
        Cache::invalidateTags([$this->entityTypeId . '_values']);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function buildCacheId($id) {
    return "values:{$this->entityTypeId}:$id";
  }

  /**
   * {@inheritdoc}
   */
  public function idExists(string $id, string $graph = NULL): bool {
    $id = SparqlArg::uri($id);
    $predicates = SparqlArg::serializeUris($this->bundlePredicate, ' ');
    if ($graph) {
      $graph = SparqlArg::uri($graph);
      $query = "ASK WHERE { GRAPH $graph { $id ?type ?o . VALUES ?type { $predicates } } }";
    }
    else {
      $query = "ASK { $id ?type ?value . VALUES ?type { $predicates } }";
    }

    return $this->sparql->query($query)->isTrue();
  }

  /**
   * Validates a list of graphs and provide defaults.
   *
   * @param string[]|null $graph_ids
   *   An ordered list of candidate graph IDs.
   * @param bool $check_all_graphs
   *   (optional) If to check all graphs. By default, only the default graphs
   *   are checked.
   *
   * @throws \InvalidArgumentException
   *   If at least one of passed graphs doesn't exist for this entity type.
   */
  protected function checkGraphs(array &$graph_ids = NULL, bool $check_all_graphs = FALSE): void {
    if (!$graph_ids) {
      if ($check_all_graphs) {
        // No passed graph means "all graphs for this entity type".
        $graph_ids = $this->getGraphHandler()->getEntityTypeGraphIds($this->getEntityTypeId());
      }
      else {
        // No passed graph means "all default graphs for this entity type".
        $graph_ids = $this->getGraphHandler()->getEntityTypeDefaultGraphIds($this->getEntityTypeId());
      }
      return;
    }

    $entity_type_graph_ids = $this->getGraphHandler()->getEntityTypeGraphIds($this->getEntityTypeId());

    // Validate each passed graph.
    array_walk($graph_ids, function (string $graph_id) use ($entity_type_graph_ids): void {
      if (!in_array($graph_id, $entity_type_graph_ids)) {
        throw new \InvalidArgumentException("Graph '$graph_id' doesn't exist for entity type '{$this->getEntityTypeId()}'.");
      }
    });
  }

  /**
   * Keep track of the originating graph of an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   */
  protected function trackOriginalGraph(EntityInterface $entity): void {
    // Store the graph ID of the loaded entity to be, eventually, used when this
    // entity gets saved. During the saving process, this value is passed to
    // SparqlEntityStorage::loadUnchanged() to correctly determine the
    // original entity graph. This value persists in entity over an entity form
    // submit, as the entity is stored in the form state, so that the entity
    // save can rely on it.
    // @see \Drupal\sparql_entity_storage\SparqlEntityStorage::doPreSave()
    // @see \Drupal\Core\Entity\EntityForm
    $entity->sparqlEntityOriginalGraph = $entity->get('graph')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultipleRevisionsFieldItems($revision_ids) {
    return [];
  }

}
