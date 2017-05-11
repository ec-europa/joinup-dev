<?php

namespace Drupal\rdf_entity\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use Drupal\rdf_entity\Entity\Query\Sparql\SparqlArg;
use Drupal\rdf_entity\Exception\DuplicatedIdException;
use Drupal\rdf_entity\RdfEntityIdPluginManager;
use Drupal\rdf_entity\RdfGraphHandler;
use Drupal\rdf_entity\RdfFieldHandler;
use EasyRdf\Graph;
use EasyRdf\Literal;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a entity storage backend that uses a Sparql endpoint.
 */
class RdfEntitySparqlStorage extends ContentEntityStorageBase {
  // @Todo Create a proper interface that this class implements...
  /**
   * Sparql database connection.
   *
   * @var \Drupal\rdf_entity\Database\Driver\sparql\Connection
   */
  protected $sparql;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The default bundle predicate.
   *
   * @var array
   */
  protected $bundlePredicate = ['http://www.w3.org/1999/02/22-rdf-syntax-ns#type'];

  /**
   * The default rdf predicate for the bundle field.
   *
   * @var string
   *
   * @todo: We should be able to get rid of this and use $bundlePredicate.
   */
  protected $rdfBundlePredicate = 'rdf:type';

  /**
   * The rdf graph helper service object.
   *
   * @var \Drupal\rdf_entity\RdfGraphHandler
   */
  protected $graphHandler;

  /**
   * The rdf mapping helper service object.
   *
   * @var \Drupal\rdf_entity\RdfFieldHandler
   */
  protected $fieldHandler;

  /**
   * The RDF entity ID generator plugin manager.
   *
   * @var \Drupal\rdf_entity\RdfEntityIdPluginManager
   */
  protected $entityIdPluginManager;

  /**
   * Initialize the storage backend.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type this storage is about.
   * @param \Drupal\rdf_entity\Database\Driver\sparql\Connection $sparql
   *   The connection object.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\rdf_entity\RdfGraphHandler $rdf_graph_handler
   *   The rdf graph helper service.
   * @param \Drupal\rdf_entity\RdfFieldHandler $rdf_field_handler
   *   The rdf mapping helper service.
   * @param \Drupal\rdf_entity\RdfEntityIdPluginManager $entity_id_plugin_manager
   *   The RDF entity ID generator plugin manager.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $sparql, EntityManagerInterface $entity_manager, EntityTypeManagerInterface $entity_type_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, ModuleHandlerInterface $module_handler, RdfGraphHandler $rdf_graph_handler, RdfFieldHandler $rdf_field_handler, RdfEntityIdPluginManager $entity_id_plugin_manager) {
    parent::__construct($entity_type, $entity_manager, $cache);
    $this->sparql = $sparql;
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->graphHandler = $rdf_graph_handler;
    $this->fieldHandler = $rdf_field_handler;
    $this->entityIdPluginManager = $entity_id_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('sparql_endpoint'),
      $container->get('entity.manager'),
      $container->get('entity_type.manager'),
      $container->get('cache.entity'),
      $container->get('language_manager'),
      $container->get('module_handler'),
      $container->get('sparql.graph_handler'),
      $container->get('sparql.field_handler'),
      $container->get('plugin.manager.rdf_entity.id')
    );
  }

  /**
   * Build a new graph (list of triples).
   *
   * @param string $graph_uri
   *   The uri of the graph.
   *
   * @return \EasyRdf\Graph
   *   The EasyRdf graph object.
   */
  protected static function getGraph($graph_uri) {
    $graph = new Graph($graph_uri);
    return $graph;
  }

  /**
   * The predicate used to determine the bundle.
   */
  public function bundlePredicate() {
    return $this->bundlePredicate;
  }

  /**
   * Returns the graph handler object.
   */
  public function getGraphHandler() {
    return $this->graphHandler;
  }

  /**
   * Get the defined graph types for this entity type.
   *
   * This is here for convenience.
   *
   * @see \Drupal\rdf_entity\RdfGraphHandler::getGraphDefinitions
   *
   * @return array
   *   A structured array of graph definitions containing a title and a
   *   description. The array keys are the machine names of the graphs.
   */
  public function getGraphDefinitions() {
    return $this->getGraphHandler()->getGraphDefinitions($this->entityTypeId);
  }

  /**
   * Set the graph type to use when interacting with entities.
   *
   * @param string $entity_id
   *   The entity id associated with the requested graphs.
   * @param array $graph_types
   *   An array of graph machine names.
   *
   * @see \Drupal\rdf_entity\RdfGraphHandler::setRequestGraphs
   */
  public function setRequestGraphs($entity_id, array $graph_types) {
    $this->getGraphHandler()->setRequestGraphs($entity_id, $this->entityTypeId, $graph_types);
  }

  /**
   * Returns the active graphs.
   *
   * @param string $entity_id
   *   The entity id associated with the requested graphs.
   *
   * @return array
   *   An array of graph ids related to the passed entity id.
   *
   * @see \Drupal\rdf_entity\RdfGraphHandler::getRequestGraphs
   */
  public function getRequestGraphs($entity_id) {
    return $this->getGraphHandler()->getRequestGraphs($entity_id);
  }

  /**
   * Get the (active) graph URI for a given bundle.
   */
  public function getBundleGraphUri($bundle, $graph_type) {
    return $this->getGraphHandler()->getBundleGraphUri($this->entityType->getBundleEntityType(), $bundle, $graph_type);
  }

  /**
   * Set the save graph.
   *
   * @param string $graph
   *   The graph to use.
   */
  public function setSaveGraph($graph) {
    $this->getGraphHandler()->setTargetGraph($graph);
  }

  /**
   * Get the graph URIs for each bundle.
   *
   * @param array $graph_types
   *   Optionally filter the retrieved graphs. If empty, all available graphs
   *   will be loaded.
   *
   * @return array
   *   An array with the graph uris as keys and the corresponding bundles as
   *   values.
   *
   * @see \Drupal\rdf_entity\GraphHandler::getEntityTypeGraphUris
   */
  public function getEntityTypeGraphUris(array $graph_types = NULL) {
    return $this->getGraphHandler()->getEntityTypeGraphUris($this->entityType->getBundleEntityType(), $graph_types);
  }

  /**
   * {@inheritdoc}
   */
  public function doLoadMultiple(array $ids = NULL) {
    // Attempt to load entities from the persistent cache. This will remove IDs
    // that were loaded from $ids.
    $entities_from_cache = $this->getFromPersistentCache($ids);
    // Load any remaining entities from the database.
    $entities_from_storage = $this->getFromStorage($ids);

    return $entities_from_cache + $entities_from_storage;
  }

  /**
   * Gets entities from the storage.
   *
   * @param array|null $ids
   *   If not empty, return entities that match these IDs. Return all entities
   *   when NULL.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   Array of entities from the storage.
   */
  protected function getFromStorage(array $ids = NULL) {
    if (empty($ids)) {
      return [];
    }
    $remaining_ids = $ids;
    $entities = [];
    while (count($remaining_ids)) {
      $operation_ids = array_slice($remaining_ids, 0, 50, TRUE);
      foreach ($operation_ids as $k => $v) {
        unset($remaining_ids[$k]);
      }
      $entities_values = $this->loadFromStorage($operation_ids);
      if ($entities_values) {
        foreach ($entities_values as $id => $entity_values) {
          $bundle = $this->bundleKey ? $entity_values[$this->bundleKey][LanguageInterface::LANGCODE_DEFAULT] : FALSE;
          $entity = new $this->entityClass($entity_values, $this->entityTypeId, $bundle);
          $entities[$id] = $entity;
        }
        $this->invokeStorageLoadHook($entities);
        $this->setPersistentCache($entities);
      }
    }
    return $entities;
  }

  /**
   * Retrieve the entity data from the Sparql endpoint.
   */
  protected function loadFromStorage($ids) {
    if (empty($ids)) {
      return [];
    }

    // @todo: We should filter per entity per graph and not load the whole
    // database only to filter later on.
    $ids_string = SparqlArg::serializeUris($ids, ' ');
    $graphs = $this->getGraphHandler()->getEntityTypeGraphUrisList($this->getEntityType()->getBundleEntityType());
    $named_graph = '';
    foreach ($graphs as $graph) {
      $named_graph .= 'FROM NAMED ' . SparqlArg::uri($graph) . "\n";
    }

    // @todo Get rid of the language filter. It's here because of eurovoc:
    // \Drupal\taxonomy\Form\OverviewTerms::buildForm loads full entities
    // of the whole tree: 7000+ terms in 24 languages is just too much.
    $query = <<<QUERY
SELECT ?graph ?entity_id ?predicate ?field_value
$named_graph
WHERE{
  GRAPH ?graph {
    ?entity_id ?predicate ?field_value .
    VALUES ?entity_id { $ids_string } .
    FILTER(!isLiteral(?field_value) || (lang(?field_value) = "" || langMatches(lang(?field_value), "EN")))
  }
}
QUERY;

    $entity_values = $this->sparql->query($query);
    return $this->processGraphResults($entity_values);
  }

  /**
   * Processes results from the load query and returns a list of values.
   *
   * @todo Reduce the cyclomatic complexity of this function.
   *
   * When an entity is loaded, the values might derive from multiple graph.
   * This function will process the results and attempt to load a published
   * version of the entity.
   * If there is no published version available, then it will fallback to the
   * rest of the graphs.
   *
   * If the graph parameter can be used to restrict the available graphs to load
   * from.
   *
   * The results array is an array of loaded entity values from different
   * graphs.
   * @code
   *    $results = [
   *      'http://entity_id.uri' => [
   *        'http://field.mapping.uri' => [
   *          'x-default' => [
   *            0 => 'actual value'
   *          ]
   *        ]
   *      ];
   * @code
   *
   * @param \EasyRdf\Sparql\Result|\EasyRdf\Graph $results
   *   A set of query results indexed per graph and entity id.
   *
   * @return array
   *   The entity values indexed by the field mapping id.
   *
   * @throws \Exception
   *    Thrown when the entity graph is empty.
   */
  protected function processGraphResults($results) {
    $inbound_map = $this->fieldHandler->getInboundMap($this->entityTypeId);
    // If no graphs are passed, fetch all available graphs derived from the
    // results.
    $values_per_entity = [];
    foreach ($results as $result) {
      $entity_id = (string) $result->entity_id;
      $entity_graphs[$entity_id] = (string) $result->graph;

      $lang = LanguageInterface::LANGCODE_DEFAULT;
      if ($result->field_value instanceof Literal) {
        $lang_temp = $result->field_value->getLang();
        if ($lang_temp) {
          $lang = $lang_temp;
        }
      }
      $values_per_entity[$entity_id][(string) $result->graph][(string) $result->predicate][$lang][] = (string) $result->field_value;
    }

    if (empty($values_per_entity)) {
      return NULL;
    }

    $return = [];
    foreach ($values_per_entity as $entity_id => $values_per_graph) {
      $request_graphs = $this->getGraphHandler()->getRequestGraphs($entity_id);
      $entity_graph_uris = $this->getGraphHandler()->getEntityTypeGraphUris($this->getEntityType()->getBundleEntityType());
      foreach ($request_graphs as $priority_graph) {
        foreach ($values_per_graph as $graph_uri => $entity_values) {
          if (isset($return[$entity_id]) || array_search($graph_uri, array_column($entity_graph_uris, $priority_graph)) === FALSE) {
            continue;
          }

          /** @var \Drupal\rdf_entity\Entity\RdfEntityType $bundle */
          $bundle = $this->getActiveBundle($entity_values);
          if (!$bundle) {
            continue;
          }

          // Check if the graph checked is in the request graphs.
          // If there are multiple graphs set, probably the default is requested
          // with the rest as fallback or it is a neutral call.
          // If the default is requested, it is going to be first in line so in
          // any case, use the first one.
          $graph_id = $this->getGraphHandler()->getBundleGraphId($this->entityType->getBundleEntityType(), $bundle, $graph_uri);

          // Map bundle and entity id.
          $return[$entity_id][$this->bundleKey][LanguageInterface::LANGCODE_DEFAULT] = $bundle;
          $return[$entity_id][$this->idKey][LanguageInterface::LANGCODE_DEFAULT] = $entity_id;
          $return[$entity_id]['graph'][LanguageInterface::LANGCODE_DEFAULT] = $graph_id;

          $rdf_type = NULL;
          foreach ($entity_values as $predicate => $field) {
            $field_name = isset($inbound_map['fields'][$predicate][$bundle]['field_name']) ? $inbound_map['fields'][$predicate][$bundle]['field_name'] : NULL;
            if (empty($field_name)) {
              continue;
            }

            $column = $inbound_map['fields'][$predicate][$bundle]['column'];
            foreach ($field as $lang => $items) {
              foreach ($items as $item) {
                if ($this->fieldHandler->isFieldSerializable($this->getEntityTypeId(), $field_name, $column)) {
                  $item = unserialize($item);
                }
                if (!isset($return[$entity_id][$field_name]) || !is_string($return[$entity_id][$field_name][$lang])) {
                  $return[$entity_id][$field_name][$lang][][$column] = $item;
                }
              }
              if (is_array($return[$entity_id][$field_name][$lang])) {
                $this->applyFieldDefaults($inbound_map['fields'][$predicate][$bundle]['type'], $return[$entity_id][$field_name][$lang]);
              }
              if (!isset($return[$entity_id][$field_name][LanguageInterface::LANGCODE_DEFAULT])) {
                $langcode = $this->languageManager->getDefaultLanguage()->getId();
                if (isset($langcode)) {
                  $return[$entity_id][$field_name][LanguageInterface::LANGCODE_DEFAULT] = $return[$entity_id][$field_name][$langcode];
                }
              }
            }
          }
        }
      }
    }
    return $return;
  }

  /**
   * Derive the bundle from the rdf:type.
   */
  protected function getActiveBundle($entity_values) {
    $bundle_predicates = $this->bundlePredicate;
    $bundles = [];
    foreach ($bundle_predicates as $bundle_predicate) {
      if (isset($entity_values[$bundle_predicate])) {
        $bundle_data = $entity_values[$bundle_predicate];
        $bundles += $this->fieldHandler->getInboundBundleValue($this->entityTypeId, $bundle_data[LanguageInterface::LANGCODE_DEFAULT][0]);
      }
    }
    if (empty($bundles)) {
      return;
    }

    // Since it is possible to map more than one bundles to the same uri, allow
    // modules to handle this.
    $this->moduleHandler->alter('rdf_load_bundle', $entity_values, $bundles);
    if (count($bundles) > 1) {
      throw new \Exception('More than one bundles are defined for this uri.');
    }
    return reset($bundles);
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    $entities = $this->loadMultiple([$id]);
    return array_shift($entities);
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = NULL) {
    $entities = parent::loadMultiple($ids);
    $uuid_key = $this->entityType->getKey('uuid');
    array_walk($entities, function (ContentEntityInterface $rdf_entity) use ($uuid_key) {
      // The ID of 'rdf_entity' is universally unique because it's a URI. As
      // the backend schema has no UUID, ID is reused as UUID.
      $rdf_entity->set($uuid_key, $rdf_entity->id());
    });
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function loadRevision($revision_id) {
    list($entity_id, $graph) = explode('||', $revision_id);

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
  public function deleteFromGraph($entity_id, $graph) {
    $this->getGraphHandler()->setRequestGraphs($entity_id, $this->entityTypeId, [$graph]);
    $entity = $this->load($entity_id);
    if (!empty($entity)) {
      $this->doDelete([$entity_id => $entity]);
      $this->resetCache([$entity_id]);
    }

    // Reset the request graphs for the deleted entities.
    $this->getGraphHandler()->resetRequestGraphs([$entity_id]);
  }

  /**
   * Checks if a RDF entity has a specific graph.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param string $graph
   *   The graph to be checked ('draft', etc).
   *
   * @return bool
   *   TRUE if this entity has the specified graph.
   */
  public function hasGraph(EntityInterface $entity, $graph) {
    $graph_uri = $this->graphHandler->getBundleGraphUri($entity->getEntityType()->getBundleEntityType(), $entity->bundle(), $graph);
    return $this->idExists($entity->id(), $graph_uri);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByProperties(array $values = []) {
    // If UUID is queried, just swap it with the ID. They are the same but UUID
    // is not stored, while on ID we can rely.
    $uuid_key = $this->entityType->getKey('uuid');
    if (isset($values[$uuid_key]) && !isset($values['id'])) {
      $values[$this->entityType->getKey('id')] = $values[$uuid_key];
      unset($values[$uuid_key]);
    }
    return parent::loadByProperties($values);
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
      $graphs = $this->graphHandler->getEntityTypeGraphUris($this->entityType->getBundleEntityType());
      foreach ($graphs[$keyed_entity->bundle()] as $graph_name => $graph_uri) {
        $entities_by_graph[$graph_uri][$keyed_entity->id()] = $keyed_entity;
      }
    }
    /** @var string $id */
    foreach ($entities_by_graph as $graph => $entities_to_delete) {
      $this->doDeleteFromGraph($entities_to_delete, $graph);
    }
    $this->resetCache(array_keys($keyed_entities));

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
      $graph_uri = $this->getGraphHandler()->getGraphUriFromEntity($entity);
      $entities_by_graph[$graph_uri][$id] = $entity;
    }
    foreach ($entities_by_graph as $graph => $entities_to_delete) {
      $this->doDeleteFromGraph($entities, $graph);
    }
  }

  /**
   * Construct and execute the delete query.
   *
   * @param array $entities
   *   An array of entity objects to delete.
   * @param string $graph
   *   The graph uri to delete from.
   */
  protected function doDeleteFromGraph(array $entities, $graph) {
    $entity_list = SparqlArg::serializeUris(array_keys($entities));

    $query = <<<QUERY
DELETE FROM <$graph>
{
  ?entity ?field ?value
}
WHERE
{
  ?entity ?field ?value
  FILTER(
    ?entity IN ($entity_list)
  )
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
  public function getQuery($conjunction = 'AND') {
    // Access the service directly rather than entity.query factory so the
    // storage's current entity type is used.
    $query = \Drupal::service($this->getQueryServiceName())->get($this->entityType, $conjunction, $this->graphHandler, $this->fieldHandler);

    /*
     * Hold on tight this ain't easy...
     * @todo: Get
     *
     * When the storage class supports the notion of a 'published state'
     * by implementing the published interface, we then have to determine
     * if drafting has been enabled for this entity type (rdf_draft module).
     * If so, the 'draft' graph will hold the unpublished versions, 'default'
     * graph contains the published entities.
     */
    if (in_array('Drupal\Core\Entity\EntityPublishedInterface', class_implements($this->entityClass))) {
      if ($this->moduleHandler->moduleExists('rdf_draft')) {
        $query->setGraphType(['draft', 'default']);
      }
    }

    return $query;
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

    // If the target graph is set, it has priority over the one the entity is
    // loaded from. If no target graph is set, use the previous one.
    $target_graph = $this->getGraphHandler()->getTargetGraphFromEntity($entity);
    $graph_uri = $this->getBundleGraphUri($bundle, $target_graph);
    $graph = self::getGraph($graph_uri);

    foreach ($entity->toArray() as $field_name => $field) {
      foreach ($field as $field_item) {
        foreach ($field_item as $column => $value) {
          // Filter out empty values or non mapped fields. The id is also
          // excluded as it is not mapped.
          if ($value === NULL || $value === '' || !$this->fieldHandler->hasFieldPredicate($this->getEntityTypeId(), $field_name, $column, $bundle)) {
            continue;
          }
          $predicate = $this->fieldHandler->getFieldPredicates($this->getEntityTypeId(), $field_name, $column, $bundle);
          $predicate = reset($predicate);
          $lang = $this->resolveFieldLangcode($entity, $entity->get($field_name)->first());
          $value = $this->fieldHandler->getOutboundValue($this->getEntityTypeId(), $field_name, $value, $lang, $column);
          $graph->add((string) $id, $predicate, $value);
        }
      }
    }

    // Give implementations a chance to alter the graph before is saved.
    $this->alterGraph($graph, $entity);

    if (!$entity->isNew()) {
      $this->deleteBeforeInsert($id, $graph_uri);
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
   * Resolves the language based on entity and current site language.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param \Drupal\Core\Field\FieldItemInterface $field_item
   *   The field for which to resolve the language.
   *
   * @return string|null
   *   A language code or NULL, if the field has no language.
   */
  protected function resolveFieldLangcode(EntityInterface $entity, FieldItemInterface $field_item) {
    if (!$langcode = $field_item->getLangcode()) {
      return NULL;
    }

    $non_languages = [
      LanguageInterface::LANGCODE_NOT_SPECIFIED,
      LanguageInterface::LANGCODE_DEFAULT,
      LanguageInterface::LANGCODE_NOT_APPLICABLE,
      LanguageInterface::LANGCODE_SITE_DEFAULT,
      LanguageInterface::LANGCODE_SYSTEM,
    ];

    // Accept only real languages or NULL.
    if (in_array($langcode, $non_languages)) {
      $langcode = $this->languageManager->getCurrentLanguage()->getId();
      if (in_array($langcode, $non_languages)) {
        return NULL;
      }
    }

    return $langcode;
  }

  /**
   * Alters the graph before saving the entity.
   *
   * Implementation are able to change, delete or add items to the graph before
   * this is saved to SPARQL backend.
   *
   * @param \EasyRdf\Graph $graph
   *   The graph to be altered.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being saved.
   */
  protected function alterGraph(Graph &$graph, EntityInterface $entity) {}

  /**
   * Get the schema definition for a given field column.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field.
   * @param string $column
   *   The column name.
   *
   * @return mixed
   *   The field column schema.
   */
  protected function getColumnSchema(FieldItemInterface $item, $column) {
    $schema = $item->getFieldDefinition()->getFieldStorageDefinition()->getSchema();
    return $schema['columns'][$column];
  }

  /**
   * Insert a graph of triples.
   *
   * @param \EasyRdf\Graph $graph
   *   The graph to insert.
   * @param string $graphUri
   *   Graph to save to.
   *
   * @return \EasyRdf\Graph|\EasyRdf\Sparql\Result
   *   Response.
   */
  private function insert(Graph $graph, $graphUri) {
    $graphUri = SparqlArg::uri($graphUri);
    $query = "INSERT DATA INTO $graphUri {\n";
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
   * Allow overrides for some field types.
   *
   * @param string $type
   *   The field type.
   * @param array $values
   *   The field values.
   *
   * @todo: To be removed when columns will be supported. No need to manually
   * set this.
   */
  private function applyFieldDefaults($type, array &$values) {
    if (empty($values)) {
      return;
    }
    foreach ($values as &$value) {
      // Textfield: provide default filter when filter not mapped.
      switch ($type) {
        case 'text_long':
          if (!isset($value['format'])) {
            $value['format'] = 'full_html';
          }
          break;

        // Strip timezone part in dates.
        case 'datetime':
          $time_stamp = strtotime($value['value']);
          $date = date('o-m-d', $time_stamp) . "T" . date('H:i:s', $time_stamp);
          $value['value'] = $date;
          break;
      }
    }
    $this->moduleHandler->alter('rdf_apply_default_fields', $type, $values);
  }

  /**
   * {@inheritdoc}
   *
   * If there are more than one graphs in the request, return the first
   * available with an entity in it.
   */
  protected function getFromStaticCache(array $ids) {
    $entities = [];
    foreach ($ids as $id) {
      $request_graphs = $this->getRequestGraphs($id);
      foreach ($request_graphs as $request_graph) {
        if (isset($this->entities[$id][$request_graph])) {
          if (!isset($entities[$id])) {
            $entities[$id] = $this->entities[$id][$request_graph];
          }
        }
      }
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  protected function setStaticCache(array $entities) {
    foreach ($entities as $id => $entity) {
      // The target graph should be empty since it's a load so the one from the
      // entity should be loaded here.
      $graph = $this->getGraphHandler()->getTargetGraphFromEntity($entity);
      if ($this->entityType->isStaticallyCacheable()) {
        $this->entities[$id][$graph] = $entity;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getFromPersistentCache(array &$ids = NULL) {
    if (!$this->entityType->isPersistentlyCacheable() || empty($ids)) {
      return [];
    }
    $entities = [];
    // Build the list of cache entries to retrieve.
    $cid_map = [];
    foreach ($ids as $id) {
      $request_graphs = $this->getGraphHandler()->getRequestGraphs($id);
      $graph = reset($request_graphs);
      $cid_map[$id] = "{$this->buildCacheId($id)}:{$graph}";
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
      $graph = $this->getGraphHandler()->getTargetGraphFromEntity($entity);
      $cid = "{$this->buildCacheId($id)}:{$graph}";
      $this->cacheBackend->set($cid, $entity, CacheBackendInterface::CACHE_PERMANENT, $cache_tags);
    }
  }

  /**
   * {@inheritdoc}
   *
   * Since the notion of graphs exist in the sparql storage, the cache reset
   * should remove entities from all graphs.
   */
  public function resetCache(array $ids = NULL) {
    $graphs = $this->getGraphHandler()->getEntityTypeEnabledGraphs();
    if ($ids) {
      $cids = [];
      foreach ($ids as $id) {
        foreach ($graphs as $graph) {
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
   * Delete an entity before it gets saved.
   *
   * The difference between deleteBeforeInsert and delete method is the
   * properties_list variable. Filtering the fields to be deleted using this
   * variable, ensures that additional data that might be imported through an
   * external repository are not lost during an entity update.
   *
   * @param string $id
   *   The entity uri.
   * @param string $graph_uri
   *   The graph uri.
   */
  protected function deleteBeforeInsert($id, $graph_uri) {
    $property_list = $this->fieldHandler->getPropertyListToArray($this->getEntityTypeId());
    $serialized = SparqlArg::serializeUris($property_list);
    $id = SparqlArg::uri($id);
    $graph_uri = SparqlArg::uri($graph_uri);
    $query = <<<QUERY
DELETE {
  GRAPH $graph_uri {
    $id ?field ?value
  }
}
WHERE {
  GRAPH $graph_uri {
    $id ?field ?value .
    FILTER (?field IN ($serialized))
  }
}
QUERY;
    $this->sparql->query($query);
  }

  /**
   * Checks if a specific entity ID already exists in the backend.
   *
   * @param string $id
   *   The ID to be checked.
   * @param string $graph
   *   The bundle resource uri. If passed, the id will be checked only against
   *   this graph.
   *
   * @return bool
   *   TRUE if this entity ID already exists, FALSE otherwise.
   */
  public function idExists($id, $graph = NULL) {
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

}
