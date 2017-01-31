<?php

namespace Drupal\rdf_entity\Entity;

use Drupal\Component\Uuid\Php;
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
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use Drupal\rdf_entity\RdfGraphHandler;
use Drupal\rdf_entity\RdfMappingHandler;
use EasyRdf\Graph;
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
   * @var \Drupal\rdf_entity\RdfMappingHandler
   */
  protected $mappingHandler;

  /**
   * Initialize the storage backend.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *    The entity type this storage is about.
   * @param \Drupal\rdf_entity\Database\Driver\sparql\Connection $sparql
   *    The connection object.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *    The entity manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *    The entity type manager service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *    The cache backend service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *    The language manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *    The module handler service.
   * @param \Drupal\rdf_entity\RdfGraphHandler $rdf_graph_handler
   *    The rdf graph helper service.
   * @param \Drupal\rdf_entity\RdfMappingHandler $rdf_mapping_handler
   *    The rdf mapping helper service.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $sparql, EntityManagerInterface $entity_manager, EntityTypeManagerInterface $entity_type_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, ModuleHandlerInterface $module_handler, RdfGraphHandler $rdf_graph_handler, RdfMappingHandler $rdf_mapping_handler) {
    parent::__construct($entity_type, $entity_manager, $cache);
    $this->sparql = $sparql;
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->graphHandler = $rdf_graph_handler;
    $this->mappingHandler = $rdf_mapping_handler;
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
      $container->get('sparql.mapping_handler')
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
   *    A structured array of graph definitions containing a title and a
   *   description. The array keys are the machine names of the graphs.
   */
  public function getGraphDefinitions() {
    return $this->getGraphHandler()->getGraphDefinitions($this->entityTypeId);
  }

  /**
   * Set the graph type to use when interacting with entities.
   *
   * @param string $entity_id
   *    The entity id associated with the requested graphs.
   * @param array $graph_types
   *    An array of graph machine names.
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
   *    The entity id associated with the requested graphs.
   *
   * @return array
   *    An array of graph ids related to the passed entity id.
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
   *    The graph to use.
   */
  public function setSaveGraph($graph) {
    $this->getGraphHandler()->setTargetGraph($graph);
  }

  /**
   * Get the graph URIs for each bundle.
   *
   * @param array $graph_types
   *    Optionally filter the retrieved graphs. If empty, all available graphs
   *   will be loaded.
   *
   * @return array
   *    An array with the graph uris as keys and the corresponding bundles as
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
    $entities = array();
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
    $ids_string = "<" . implode(">, <", $ids) . ">";
    $graphs = $this->getGraphHandler()->getEntityTypeGraphUrisList($this->getEntityType()->getBundleEntityType());
    $named_graph = '';
    foreach ($graphs as $graph) {
      $named_graph .= 'FROM NAMED <' . $graph . '>' . "\n";
    }

    // @todo Get rid of the language filter. It's here because of eurovoc:
    // \Drupal\taxonomy\Form\OverviewTerms::buildForm loads full entities
    // of the whole tree: 7000+ terms in 24 languages is just too much.
    $query = <<<QUERY
SELECT ?graph ?entity_id ?predicate ?field_value
$named_graph
WHERE{
  GRAPH ?graph {
    ?entity_id ?predicate ?field_value
    FILTER (?entity_id IN ( $ids_string ) )
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
   *    A set of query results indexed per graph and entity id.
   *
   * @return array
   *    The entity values indexed by the field mapping id.
   *
   * @throws \Exception
   *    Thrown when the entity graph is empty.
   */
  protected function processGraphResults($results) {
    $mapping = $this->mappingHandler->getEntityPredicates($this->entityTypeId);
    // If no graphs are passed, fetch all available graphs derived from the
    // results.
    $values_per_entity = [];
    foreach ($results as $result) {
      $entity_id = (string) $result->entity_id;
      $entity_graphs[$entity_id] = (string) $result->graph;

      $lang = LanguageInterface::LANGCODE_DEFAULT;
      if ($result->field_value instanceof \EasyRdf_Literal) {
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
          // First determine the bundle of the returned entity.
          $bundle_predicates = $this->bundlePredicate;
          $pred_set = FALSE;
          foreach ($bundle_predicates as $bundle_predicate) {
            if (isset($entity_values[$bundle_predicate])) {
              $pred_set = TRUE;
            }
          }
          if (!$pred_set) {
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
          $graph_id = $this->getGraphHandler()->getBundleGraphId($this->entityType->getBundleEntityType(), $bundle->id(), $graph_uri);

          // Map bundle and entity id.
          $return[$entity_id][$this->bundleKey][LanguageInterface::LANGCODE_DEFAULT] = $bundle->id();
          $return[$entity_id][$this->idKey][LanguageInterface::LANGCODE_DEFAULT] = $entity_id;
          $return[$entity_id]['graph'][LanguageInterface::LANGCODE_DEFAULT] = $graph_id;

          $rdf_type = NULL;
          foreach ($entity_values as $predicate => $field) {
            // If not mapped, ignore.
            if (!isset($mapping[$bundle->id()][$predicate])) {
              continue;
            }
            $field_name = $mapping[$bundle->id()][$predicate]['field_name'];
            $column = $mapping[$bundle->id()][$predicate]['column'];
            foreach ($field as $lang => $items) {
              foreach ($items as $item) {
                if (isset($mapping[$bundle->id()][$predicate]['storage_definition'])) {
                  /** @var FieldStorageConfig $field_storage_definition */
                  $field_storage_definition = $mapping[$bundle->id()][$predicate]['storage_definition'];
                  $field_storage_schema = $field_storage_definition->getSchema()['columns'];
                  // Inflate value back into a normal item.
                  if (isset($field_storage_schema[$column]['serialize']) && $field_storage_schema[$column]['serialize'] === TRUE) {
                    $item = unserialize($item);
                  }
                }
                if (!isset($return[$entity_id][$field_name]) || !is_string($return[$entity_id][$field_name][$lang])) {
                  $return[$entity_id][$field_name][$lang][][$column] = $item;
                }
                if (!isset($return[$entity_id][$field_name][LanguageInterface::LANGCODE_DEFAULT])) {
                  $return[$entity_id][$field_name][LanguageInterface::LANGCODE_DEFAULT][][$column] = $item;
                }
              }
              if (isset($mapping[$bundle->id()][$predicate]['storage_definition'])) {
                $storage_definition = $mapping[$bundle->id()][$predicate]['storage_definition'];
                $this->applyFieldDefaults($storage_definition, $return[$entity_id][$storage_definition->getName()][$lang]);
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
    $bundle = NULL;
    static $rdf_bundles;
    if (!isset($rdf_bundles[$this->entityType->getBundleEntityType()])) {
      $rdf_bundles[$this->entityType->getBundleEntityType()] = $this->entityTypeManager->getStorage($this->entityType->getBundleEntityType())->loadMultiple();
    }
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $rdf_bundle */
    foreach ($rdf_bundles[$this->entityType->getBundleEntityType()] as $rdf_bundle) {
      $settings = rdf_entity_get_third_party_property($rdf_bundle, 'mapping', $this->bundleKey, FALSE);
      $type = array_pop($settings);
      foreach ($this->bundlePredicate as $bundlePredicate) {
        if (!isset($entity_values[$bundlePredicate])) {
          continue;
        }
        foreach ($entity_values[$bundlePredicate] as $lang => $items) {
          foreach ($items as $item) {
            if ($item == $type) {
              $bundle = $rdf_bundle;
            }
          }
        }
      }
    }
    $this->moduleHandler->alter('rdf_load_bundle', $entity_values, $bundle);
    return $bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    $entities = $this->loadMultiple(array($id));
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
    /** @var ContentEntityInterface $entity */
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
    $entity_list = "<" . implode(">, <", array_keys($entities)) . ">";

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
    $query = \Drupal::service($this->getQueryServiceName())
      ->get($this->entityType, $conjunction);

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
      if (\Drupal::moduleHandler()->moduleExists('rdf_draft')) {
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
    return array();
  }

  /**
   * {@inheritdoc}
   */
  protected function purgeFieldItems(ContentEntityInterface $entity, FieldDefinitionInterface $field_definition) {
  }

  /**
   * Generate the id of the entity.
   *
   * @return string
   *    The new id for the entity.
   */
  protected function generateId() {
    $uuid = new Php();
    // @todo Fetch a bundle specific template.
    return 'http://placeHolder/' . $uuid->generate();
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
      $id = $this->generateId();
      $entity->{$this->idKey} = (string) $id;
    }
    elseif ($entity->isNew() && $this->idExists($id)) {
      throw new \InvalidArgumentException("Attempting to create a new entity with the ID '$id' already taken.");
    }

    // If the target graph is set, it has priority over the one the entity is
    // loaded from. If no target graph is set, use the previous one.
    $target_graph = $this->getGraphHandler()->getTargetGraphFromEntity($entity);
    $graph_uri = $this->getBundleGraphUri($bundle, $target_graph);

    $graph = self::getGraph($graph_uri);

    $properties = $this->mappingHandler->getEntityTypeMappedProperties($entity);
    $properties_list = "<" . implode(">, <", $properties['flat']) . ">";
    foreach ($entity->toArray() as $field_name => $field) {
      foreach ($field as $field_item) {
        foreach ($field_item as $column => $value) {
          // No mapping for this column set.
          if (!isset($properties['by_field'][$field_name][$column])) {
            continue;
          }
          // Skip the bundle as it is handled separately later.
          if ($field_name == 'rid') {
            continue;
          }
          /** @var \Drupal\Core\Field\FieldItemList $field_item_list */
          $field_item_list = $entity->get($field_name);
          // Don't add empty values.
          if ($field_item_list->isEmpty()) {
            continue;
          }
          $item = $entity->get($field_name)->first();

          $column_schema = $this->getColumnSchema($item, $column);
          // Take care of serialized fields.
          // @todo Could this be replaced with something more interoperable?
          // (json?, bnodes?)
          if (isset($column_schema['serialize']) && $column_schema['serialize'] == TRUE) {
            $value = serialize($value);
          }
          // When the field is a entity reference, and it's target implements
          // RdfEntitySparqlStorage (it's an RDF based entity),
          // then store it as a resource.
          if ($this->fieldIsReference($item)) {
            $graph->addResource((string) $id, (string) $properties['by_field'][$field_name][$column], $value);
          }
          // All other fields get stored as a literal.
          else {
            // @todo Set language and field data type.
            $graph->addLiteral((string) $id, (string) $properties['by_field'][$field_name][$column], $value);
          }
        }
      }
    }
    // Save the bundle as rdf:type.
    $rdf_bundle_mapping = $this->mappingHandler->getRdfBundleMappedUri($entity->getEntityType()->getBundleEntityType(), $entity->bundle());
    $rdf_bundle = $rdf_bundle_mapping[$entity->bundle()];
    $graph->addResource((string) $id, $this->rdfBundlePredicate, $rdf_bundle);

    if (!$entity->isNew()) {
      $this->deleteBeforeInsert($id, $graph_uri, $properties_list);
    }
    // @todo Do in one transaction... If possible.
    $this->insert($graph, $graph_uri);

  }

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
    $query = "INSERT DATA INTO <$graphUri> {\n";
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
   * @param FieldStorageConfig $storage
   *   Field storage configuration.
   * @param array $values
   *   The field values.
   */
  private function applyFieldDefaults(FieldStorageConfig $storage, array &$values) {
    if (empty($values)) {
      return;
    }
    foreach ($values as &$value) {
      // Textfield: provide default filter when filter not mapped.
      switch ($storage->getType()) {
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
    $this->moduleHandler->alter('rdf_apply_default_fields', $storage, $values);
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
      return array();
    }
    $entities = array();
    // Build the list of cache entries to retrieve.
    $cid_map = array();
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

    $cache_tags = array(
      $this->entityTypeId . '_values',
      'entity_field_info',
    );
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
      $cids = array();
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
   * Determines if a field is an entity reference.
   *
   * @param FieldItemInterface $item
   *   The field.
   *
   * @return bool
   *   Is a reference
   */
  protected function fieldIsReference(FieldItemInterface $item) {
    if (!$item instanceof EntityReferenceItem) {
      return FALSE;
    }
    /** @var \Drupal\Core\Entity\Plugin\DataType\EntityReference $entity_property */
    $entity_property = $item->get('entity');
    $target = $entity_property->getTarget();
    if (empty($target) || $target->isEmpty()) {
      return FALSE;
    }
    /** @var EntityInterface $target_entity */
    $target_entity = $target->getValue();
    $target_entity_type = $target_entity->getEntityType();
    $target_entity_storage_class = trim($target_entity_type->getStorageClass(), "\\");
    $classes = class_parents($target_entity_storage_class);
    $classes[$target_entity_storage_class] = $target_entity_storage_class;
    return in_array(RdfEntitySparqlStorage::class, $classes);
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
   *    The entity uri.
   * @param string $graph_uri
   *    The graph uri.
   * @param string $properties_list
   *    A string resulting after a conversion of an array to the SPARQL uri
   *    array format.
   */
  protected function deleteBeforeInsert($id, $graph_uri, $properties_list) {
    $query = <<<QUERY
DELETE {
  GRAPH <$graph_uri> {
    <$id> ?field ?value
  }
}
WHERE {
  GRAPH <$graph_uri> {
    <$id> ?field ?value .
    FILTER (?field IN ($properties_list))
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
   *
   * @return bool
   *   TRUE if this entity ID already exists, FALSE otherwise.
   */
  protected function idExists($id) {
    $query = <<<QUERY
ASK {
  <$id> ?field ?value
}
QUERY;
    return $this->sparql->query($query)->isTrue();
  }

}
