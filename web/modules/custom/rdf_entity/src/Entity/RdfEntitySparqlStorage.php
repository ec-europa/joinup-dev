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
use Drupal\rdf_entity\RdfGraphHelper;
use Drupal\rdf_entity\RdfMappingHelper;
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

  protected $bundlePredicate = ['http://www.w3.org/1999/02/22-rdf-syntax-ns#type'];

  protected $activeGraph = ['default'];

  protected $saveGraph = NULL;

  /**
   * The default rdf predicate for the bundle field.
   *
   * @var string
   */
  protected $rdf_bundle_predicate = 'rdf:type';

  /**
   * The rdf graph helper service object.
   *
   * @var \Drupal\rdf_entity\RdfGraphHelper
   */
  protected $graphHelper = NULL;

  /**
   * The rdf mapping helper service object.
   *
   * @var \Drupal\rdf_entity\RdfMappingHelper
   */
  protected $mappingHelper = NULL;

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
   * @param \Drupal\rdf_entity\RdfGraphHelper $rdf_graph_helper
   *    The rdf graph helper service.
   * @param \Drupal\rdf_entity\RdfMappingHelper $rdf_mapping_helper
   *    The rdf mapping helper service.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $sparql, EntityManagerInterface $entity_manager, EntityTypeManagerInterface $entity_type_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, ModuleHandlerInterface $module_handler, RdfGraphHelper $rdf_graph_helper, RdfMappingHelper $rdf_mapping_helper) {
    parent::__construct($entity_type, $entity_manager, $cache);
    $this->sparql = $sparql;
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    // Allow altering the default active graph.
    $graph = $this->activeGraph;
    $this->moduleHandler->alter('rdf_default_active_graph', $entity_type, $graph);
    $this->activeGraph = $graph;
    $this->graphHelper = $rdf_graph_helper;
    $this->mappingHelper = $rdf_mapping_helper;
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
      $container->get('sparql.graph_helper'),
      $container->get('sparql.mapping_helper')
    );
  }

  /**
   * The predicate used to determine the bundle.
   */
  public function bundlePredicate() {
    return $this->bundlePredicate;
  }

  /**
   * Get the defined graph types for this entity type.
   */
  public function getGraphsDefinition() {
    $graphs_definition = [];
    $graphs_definition['default'] = [
      'title' => $this->t('Default'),
      'description' => $this->t('The default graph used to store entities of this type.'),
    ];
    // @todo Consider turning this into an event.

    $this->moduleHandler->alter('rdf_graph_definition', $this->entityTypeId, $graphs_definition);
    return $graphs_definition;
  }

  /**
   * Set the graph type to use when interacting with entities.
   *
   * @param array $graph_types
   *    An array of graph machine names.
   *
   * @throws \Exception
   *    Thrown if there is an invalid graph in the argument array or if the
   *    final array is empty as there must be at least one active graph.
   */
  public function setActiveGraphType(array $graph_types) {
    $definitions = $this->getGraphsDefinition();
    $graphs_array = [];
    foreach ($graph_types as $graph_type) {
      if (!isset($definitions[$graph_type])) {
        throw new \Exception('Unknown graph type ' . $graph_type);
      }
      $graphs_array[] = $graph_type;
    }

    // @todo: Should we have the default one set if the result set is empty?
    if (empty($graphs_array)) {
      throw new \Exception("There must be at least one active graph.");
    }

    // Remove duplicates as there might be occurances after the loop above.
    $this->activeGraph = array_unique($graphs_array);
  }

  /**
   * Get the graph type in use.
   */
  public function getActiveGraphType() {
    return $this->activeGraph;
  }

  /**
   * Get the (active) graph URI for a given bundle.
   */
  public function getGraph($bundle, $graph_type = NULL) {
    if (!$graph_type) {
      $graph_type = $this->getActiveGraphType();
    }
    $bundle = $this->entityTypeManager->getStorage($this->entityType->getBundleEntityType())->load($bundle);
    $graph = $bundle->getThirdPartySetting('rdf_entity', 'graph_' . $graph_type, FALSE);
    if (!$graph) {
      throw new \Exception(format_string('Unable to determine graph %graph for bundle %bundle', [
        '%graph' => $graph_type,
        '%bundle' => $bundle->id(),
      ]));
    }
    return $graph;
  }

  /**
   * Set the save graph.
   *
   * @param string $graph
   *    The graph to use.
   */
  public function setSaveGraph($graph) {
    $this->saveGraph = $graph;
  }

  /**
   * Get the graph URIs for each bundle.
   */
  public function getGraphs($graph_types = NULL) {
    if (!$graph_types) {
      $graph_types = $this->getActiveGraphType();
    }
    $bundles = $this->entityTypeManager->getStorage($this->entityType->getBundleEntityType())->loadMultiple();
    $graphs = [];
    foreach ($bundles as $bundle) {
      foreach ($graph_types as $graph_type) {
        $graph = $bundle->getThirdPartySetting('rdf_entity', 'graph_' . $graph_type, FALSE);
        if (!$graph) {
          throw new \Exception(format_string('Unable to determine graph "@graph" for bundle "@bundle"', [
            '@graph' => $this->getActiveGraphType(),
            '@bundle' => $bundle->id(),
          ]));
        }
        $graphs[$graph][] = $bundle->id();
      }
    }
    return $graphs;
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
    $graph_id = implode('-', $this->activeGraph);
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
    $graphs = $this->getGraphs();
    $named_graph = '';
    foreach (array_keys($graphs) as $graph) {
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
    $values = $this->processGraphResults($entity_values);
    return $values;
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
    $mapping = $this->mappingHelper->getEntityPredicates($this->entityTypeId);
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

    $return = [];
    foreach ($values_per_graph as $graph => $entity_per_graph) {
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
        $return[$entity_id]['graph'][LanguageInterface::LANGCODE_DEFAULT] = $entity_graphs[$entity_id];

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
        $entity->set('graph', $this->getGraph($entity->bundle(), 'default'));
      }
      $graph = $entity->get('graph')->first()->getValue()['value'];
      $entities_by_graph[$graph][$id] = $entity;
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
   * Get the Drupal field <-> rdf field mapping.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity.
   *
   * @return array
   *    An array of mappings between predicates and field properties.
   */
  protected function getMappedProperties(ContentEntityInterface $entity) {
    $bundle = $entity->bundle();
    $properties = [];
    $entity_manager = \Drupal::getContainer()->get('entity.manager');
    // Collect impacted fields.
    $definitions = $entity_manager->getFieldDefinitions($entity->getEntityTypeId(), $bundle);
    $base_field_definitions = $this->entityManager->getBaseFieldDefinitions($this->entityTypeId);
    $rdf_bundle_entity = $this->entityTypeManager->getStorage($this->entityType->getBundleEntityType())->load($bundle);
    /** @var \Drupal\Core\Field\BaseFieldDefinition $field_definition */
    foreach ($definitions as $field_name => $field_definition) {
      /** @var \Drupal\field\Entity\FieldStorageConfig $storage_definition */
      $storage_definition = $field_definition->getFieldStorageDefinition();
      if (!$storage_definition instanceof FieldStorageConfig) {
        continue;
      }
      foreach ($storage_definition->getColumns() as $column => $column_info) {
        if ($property = $storage_definition->getThirdPartySetting('rdf_entity', 'mapping_' . $column, FALSE)) {
          $properties['by_field'][$field_name][$column] = $property;
          $properties['flat'][$property] = $property;
        }
      }
    }
    foreach ($base_field_definitions as $field_name => $base_field_definition) {
      $field_data = $rdf_bundle_entity->getThirdPartySetting('rdf_entity', 'mapping_' . $field_name, FALSE);
      if (!$field_data) {
        continue;
      }
      foreach ($field_data as $column => $predicate) {
        if (empty($predicate)) {
          continue;
        }
        $properties['by_field'][$field_name][$column] = $predicate;
        $properties['flat'][$predicate] = $predicate;
      }

    }
    return $properties;
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
    // Force the graph.
    $loaded_from_graph = $entity->get('graph')->first()->getValue();
    if ($this->saveGraph) {
      $graph = $this->getGraph($bundle, $this->saveGraph);
    }
    elseif (!empty($loaded_from_graph)) {
      $graph = $loaded_from_graph['value'];
    }
    // Fallback.
    else {
      $enabled_bundles = \Drupal::config('rdf_draft.settings')->get('revision_bundle_' . $entity->getEntityTypeId());
      $default_save_graph = \Drupal::config('rdf_draft.settings')->get('default_save_graph_' . $entity->getEntityTypeId());
      if (!empty($enabled_bundles[$bundle])) {
        // Create new entities in the default save graph.
        $graph = $this->getGraph($bundle, $default_save_graph);
      }
      else {
        $graph = $this->getGraph($bundle, 'default');
      }
    }
    $insert = '';
    $properties = $this->getMappedProperties($entity);
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
    $rdf_bundle = $this->mappingHelper->getRdfBundleMappedUri($entity->getEntityTypeId(), $entity->bundle());
    $insert .= $subj . ' ' . $this->rdf_bundle_predicate . ' <' . $rdf_bundle . '>  .' . "\n";

    $query = <<<QUERY
DELETE {
  GRAPH <$graph> {
    <$id> ?field ?value
  }
}
WHERE {
  GRAPH <$graph> { 
    <$id> ?field ?value .
    FILTER (?field IN ($properties_list))
  }
}
QUERY;

    if (!$entity->isNew()) {
      $this->sparql->query($query);
    }
    // @todo Do in one transaction... If possible.
    $query = "INSERT DATA INTO <$graph> {\n" .
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
      $key = implode('-', $this->activeGraph);
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
    $key = implode('-', $this->activeGraph);
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
    $key = implode('-', $this->activeGraph);
    if ($this->entityType->isStaticallyCacheable()) {
      if (empty($this->entities[$key])) {
        $this->entities[$key] = [];
      }
      $this->entities[$key] += $entities;
    }
  }

}
