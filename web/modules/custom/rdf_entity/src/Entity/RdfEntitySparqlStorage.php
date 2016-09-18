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
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use Drupal\rdf_entity\RdfGraphHandler;
use Drupal\rdf_entity\RdfMappingHandler;
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
   * @param array $graph_types
   *    An array of graph machine names.
   *
   * @todo: This needs to be changed to setRequestGraphs.
   *
   * @see \Drupal\rdf_entity\RdfGraphHandler::setRequestGraphs
   */
  public function setActiveGraphType(array $graph_types) {
    $this->getGraphHandler()->setRequestGraphs($this->entityTypeId, $graph_types);
  }

  /**
   * Returns the active graphs.
   *
   * @see \Drupal\rdf_entity\RdfGraphHandler::getRequestGraphs
   */
  public function getActiveGraphType() {
    return $this->getGraphHandler()->getRequestGraphs();
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
  public function getEntityTypeGraphUris($graph_types = NULL) {
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
   * {@inheritdoc}
   */
  protected function buildCacheId($id) {
    // @todo This isn't optimal...
    $graph_id = implode('-', $this->getActiveGraphType());
    return "values:{$this->entityTypeId}:$id:{$graph_id}";
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

    $this->getGraphHandler()->resetRequestGraphs();
    return $entities;
  }

  /**
   * Retrieve the entity data from the Sparql endpoint.
   */
  protected function loadFromStorage($ids) {
    if (empty($ids)) {
      return [];
    }
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

    /** @var \EasyRdf_Sparql_Result $entity_values */
    $entity_values = $this->sparql->query($query);
    return $this->processGraphResults($entity_values);
  }

  /**
   * Processes results from the load query and returns a list of values.
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
   *      'http://example.com/entity_id/published' => [
   *        'http://entity_id.uri' => [
   *          'http://field.mapping.uri' => [
   *            'x-default' => [
   *              0 => 'actual value'
   *            ]
   *          ]
   *        ]
   *      ];
   * @code
   *
   * @param array $results
   *    A set of query results indexed per graph and entity id.
   * @param array $entity_graphs
   *    Optionally filters loading graphs available. This can be used to force
   *    it to check only e.g. for the draft version of the entity and return
   *    an empty set of values if it is not found.
   *
   * @return array
   *    The entity values indexed by the field mapping id.
   */
  protected function processGraphResults($results, $entity_graphs = []) {
    $mapping = $this->mappingHandler->getEntityPredicates($this->entityTypeId);
    // If no graphs are passed, fetch all available graphs derived from the
    // results.
    $values_per_graph = [];
    if (empty($entity_graphs)) {
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
        $values_per_graph[(string) $result->graph][$entity_id][(string) $result->predicate][$lang][] = (string) $result->field_value;
      }
    }
    if (empty($values_per_graph)) {
      return NULL;
    }

    $return = [];
    // If there are multiple graphs set, probably the default is requested with
    // the rest as fallback or it is a neutral call. In these cases, the first
    // available will be loaded.
    $entity_per_graph = reset($values_per_graph);
    foreach ($entity_per_graph as $entity_id => $entity_values) {
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
      // Map bundle and entity id.
      $return[$entity_id][$this->bundleKey][LanguageInterface::LANGCODE_DEFAULT] = $bundle->id();
      $return[$entity_id][$this->idKey][LanguageInterface::LANGCODE_DEFAULT] = $entity_id;
      $graph_id = $this->getGraphHandler()
        ->getBundleGraphId($this->entityType->getBundleEntityType(), $bundle->id(), $entity_graphs[$entity_id]);
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
    foreach ($rdf_bundles[$this->entityType->getBundleEntityType()] as $rdf_bundle) {
      $settings = $rdf_bundle->getThirdPartySetting('rdf_entity', 'mapping_' . $this->bundleKey, FALSE);
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
  public function loadByProperties(array $values = array()) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  protected function doDelete($entities) {
    $entities_by_graph = [];
    /** @var string $id */
    /** @var ContentEntityInterface $entity */
    foreach ($entities as $id => $entity) {
      if (!$entity->get('graph')->first()->getValue()) {
        $entity->set('graph', 'default');
      }
      $graph_id = $entity->get('graph')->first()->getValue()['value'];
      $graph_uri = $this->getGraphHandler()->getBundleGraphUri($entity->getEntityType()->getBundleEntityType(), $entity->bundle(), $graph_id);
      $entities_by_graph[$graph_uri][$id] = $entity;
    }
    foreach ($entities_by_graph as $graph => $entities_to_delete) {
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

    // If the target graph is set, it has priority over the one the entity is
    // loaded from. If no target graph is set, use the previous one.
    $target_graph = $this->getGraphHandler()->getTargetGraphFromEntity($entity);
    $graph_uri = $this->getBundleGraphUri($bundle, $target_graph);

    $insert = '';
    $properties = $this->mappingHandler->getEntityTypeMappedProperties($entity);
    $subj = '<' . (string) $id . '>';
    $properties_list = "<" . implode(">, <", $properties['flat']) . ">";
    foreach ($entity->toArray() as $field_name => $field) {
      foreach ($field as $field_item) {
        foreach ($field_item as $column => $value) {
          if (!isset($properties['by_field'][$field_name][$column])) {
            continue;
          }
          $pred = '<' . (string) $properties['by_field'][$field_name][$column] . '>';
          if (!filter_var($value, FILTER_VALIDATE_URL) === FALSE) {
            $obj = '<' . $value . '>';
          }
          else {
            // @todo This is most probably prone to Sparql injection..!
            $obj = '"""' . $value . '"""';
          }
          $insert .= $subj . ' ' . $pred . ' ' . $obj . '  .' . "\n";
        }
      }
    }
    // Save the bundle.
    $rdf_bundle_mapping = $this->mappingHandler->getRdfBundleMappedUri($entity->getEntityType()->getBundleEntityType(), $entity->bundle());
    $rdf_bundle = $rdf_bundle_mapping[$entity->bundle()];
    $insert .= $subj . ' ' . $this->rdfBundlePredicate . ' <' . $rdf_bundle . '>  .' . "\n";

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

    if (!$entity->isNew()) {
      $this->sparql->query($query);
    }
    // @todo Do in one transaction... If possible.
    $query = "INSERT DATA INTO <$graph_uri> {\n" .
      $insert . "\n" .
      '}';
    $this->sparql->query($query);
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
  private function applyFieldDefaults(FieldStorageConfig $storage, &$values) {
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
   */
  public function resetCache(array $ids = NULL) {
    // Consider the graph when statically caching entities.
    if ($ids) {
      $cids = array();
      $key = implode('-', $this->getActiveGraphType());
      foreach ($ids as $id) {
        unset($this->entities[$key][$id]);
        $cids[] = $this->buildCacheId($id);
      }
      if ($this->entityType->isPersistentlyCacheable()) {
        $this->cacheBackend->deleteMultiple($cids);
      }
    }
    else {
      $this->entities = array();
      if ($this->entityType->isPersistentlyCacheable()) {
        Cache::invalidateTags(array($this->entityTypeId . '_values'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getFromStaticCache(array $ids) {
    $key = implode('-', $this->getActiveGraphType());
    // Consider the graph when statically caching entities.
    $entities = array();
    // Load any available entities from the internal cache.
    if ($this->entityType->isStaticallyCacheable() && !empty($this->entities[$key])) {
      $entities += array_intersect_key($this->entities[$key], array_flip($ids));
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  protected function setStaticCache(array $entities) {
    // Consider the graph when statically caching entities.
    $key = implode('-', $this->getActiveGraphType());
    if ($this->entityType->isStaticallyCacheable()) {
      if (empty($this->entities[$key])) {
        $this->entities[$key] = [];
      }
      $this->entities[$key] += $entities;
    }
  }

}
