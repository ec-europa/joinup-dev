<?php

namespace Drupal\rdf_entity\Entity;

use Drupal\Component\Uuid\Php;
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
   * @var \Drupal\Core\Language\LanguageManagerInterface sdfsdf.
   */
  protected $languageManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface sdfsdfsdf.
   */
  protected $entityTypeManager;

  protected $bundlePredicate = ['http://www.w3.org/1999/02/22-rdf-syntax-ns#type'];

  protected $activeGraph = 'default';

  /**
   * Initialize the storage backend.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $sparql, EntityManagerInterface $entity_manager, EntityTypeManagerInterface $entity_type_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($entity_type, $entity_manager, $cache);
    $this->sparql = $sparql;
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
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
    $this->moduleHandler->alter('rdf_graph_definition', $graphs_definition);
    return $graphs_definition;
  }

  /**
   * Set the graph type to use when interacting with entities.
   */
  public function setActiveGraphType(string $graph_type) {
    $definitions = $this->getGraphsDefinition();
    if (!isset($definitions[$graph_type])) {
      throw new \Exception('Unknown graph type ' . $graph_type);
    }
    $this->activeGraph = $graph_type;
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
   * Get the graph URIs for each bundle.
   */
  public function getGraphs($graph_type = NULL) {
    if (!$graph_type) {
      $graph_type = $this->getActiveGraphType();
    }
    $bundles = $this->entityTypeManager->getStorage($this->entityType->getBundleEntityType())->loadMultiple();
    $graphs = [];
    foreach ($bundles as $bundle) {
      $graph = $bundle->getThirdPartySetting('rdf_entity', 'graph_' . $graph_type, FALSE);
      if (!$graph) {
        throw new \Exception(format_string('Unable to determine graph "!graph" for bundle "!bundle"', [
          '!graph' => $this->getActiveGraphType(),
          '!bundle' => $bundle->id(),
        ]));
      }
      $graphs[$graph][] = $bundle->id();
    }
    return $graphs;
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
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function create(array $values = array()) {
    if (!isset($values[$this->idKey])) {
      $values[$this->idKey] = $this->generateId();
    }
    // Set the graph so it can be saved correctly later.
    if (!isset($values['graph'])) {
      $values['graph'] = $this->getGraph($values[$this->bundleKey]);
    }
    return parent::create($values);
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

    $mapping = $this->predicateMapping();

    $res = [];
    foreach ($entity_values as $result) {
      $entity_id = (string) $result->entity_id;
      $entity_graph = (string) $result->graph;

      $lang = LanguageInterface::LANGCODE_DEFAULT;
      if ($result->field_value instanceof \EasyRdf_Literal) {
        $lang_temp = $result->field_value->getLang();
        if ($lang_temp) {
          $lang = $lang_temp;
        }
      }
      $res[$entity_id][(string) $result->predicate][$lang][] = (string) $result->field_value;
    }
    $values = [];
    foreach ($res as $entity_id => $entity_values) {
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
      $values[$entity_id][$this->bundleKey][LanguageInterface::LANGCODE_DEFAULT] = $bundle->id();
      $values[$entity_id][$this->idKey][LanguageInterface::LANGCODE_DEFAULT] = $entity_id;
      $values[$entity_id]['graph'] = $entity_graph;

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
            if (!isset($values[$entity_id][$field_name]) || !is_string($values[$entity_id][$field_name][$lang])) {
              $values[$entity_id][$field_name][$lang][][$column] = $item;
            }
            if (!isset($values[$entity_id][$field_name][LanguageInterface::LANGCODE_DEFAULT])) {
              $values[$entity_id][$field_name][LanguageInterface::LANGCODE_DEFAULT][][$column] = $item;
            }
          }
          if (isset($mapping[$bundle->id()][$predicate]['storage_definition'])) {
            $storage_definition = $mapping[$bundle->id()][$predicate]['storage_definition'];
            $this->applyFieldDefaults($storage_definition, $values[$entity_id][$storage_definition->getName()][$lang]);
          }
        }
      }
    }
    return $values;
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
   * Get the mapping between drupal properties and rdf predicates.
   */
  protected function predicateMapping() {
    $mapping = &drupal_static(__FUNCTION__);
    if (empty($mapping[$this->entityTypeId])) {
      // Collect entities ids, bundles and languages.
      $rdf_bundle_entities = $this->entityTypeManager->getStorage($this->entityType->getBundleEntityType())->loadMultiple();

      // Collect impacted fields.
      $mapping[$this->entityTypeId] = [];
      foreach ($rdf_bundle_entities as $rdf_bundle_entity) {
        $base_field_definitions = $this->entityManager->getBaseFieldDefinitions($this->entityTypeId);
        $field_definitions = $this->entityManager->getFieldDefinitions($this->entityTypeId, $rdf_bundle_entity->id());
        if (!$base_field_definitions) {
          continue;
        }
        foreach ($base_field_definitions as $id => $base_field_definition) {
          $field_data = $rdf_bundle_entity->getThirdPartySetting('rdf_entity', 'mapping_' . $id, FALSE);
          if (!$field_data) {
            continue;
          }
          foreach ($field_data as $column => $predicate) {
            if (empty($predicate)) {
              continue;
            }
            $mapping[$this->entityTypeId][$rdf_bundle_entity->id()][$predicate] = array(
              'field_name' => $id,
              'column' => $column,
            );
          }
        }
        foreach ($field_definitions as $field_name => $field_definition) {
          /** @var \Drupal\field\Entity\FieldStorageConfig $storage_definition */
          $storage_definition = $field_definition->getFieldStorageDefinition();
          if (!$storage_definition instanceof FieldStorageConfig) {
            continue;
          }
          foreach ($storage_definition->getColumns() as $column => $column_info) {
            if ($predicate = $storage_definition->getThirdPartySetting('rdf_entity', 'mapping_' . $column, FALSE)) {
              if (empty($predicate)) {
                continue;
              }
              $mapping[$this->entityTypeId][$rdf_bundle_entity->id()][$predicate] = array(
                'column' => $column,
                'field_name' => $field_name,
                'storage_definition' => $storage_definition,
              );
            }
          }
        }
      }
    }
    return $mapping[$this->entityTypeId];
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    $entities = $this->loadMultiple(array($id));
    return array_shift($entities);
  }

  /**
   * Get the mapping between bundle names and their rdf objects.
   */
  public function getRdfBundleMapping() {
    $bundle_rdf_bundle_mapping = array();
    foreach ($this->entityTypeManager->getStorage($this->entityType->getBundleEntityType())
               ->loadMultiple() as $entity) {
      $settings = $entity->getThirdPartySetting('rdf_entity', 'mapping_' . $this->bundleKey, FALSE);
      if (!is_array($settings)) {
        throw new \Exception('No rdf:type mapping set for bundle ' . $entity->label());
      }
      $type = array_pop($settings);
      $bundle_rdf_bundle_mapping[$this->entityTypeId][$entity->id()] = $type;
    }
    \Drupal::moduleHandler()->alter('bundle_mapping', $bundle_rdf_bundle_mapping);
    return $bundle_rdf_bundle_mapping;
  }

  /**
   * Returns an rdf object for each bundle.
   *
   * Returns the rdf object that is specific for this bundle.
   */
  public function getRdfBundleList($bundles = []) {
    $bundle_mapping = $this->getRdfBundleMapping();
    if (empty($bundle_mapping)) {
      return;
    }
    if (!$bundles) {
      $bundles = array_keys($bundle_mapping[$this->entityTypeId]);
    }
    $rdf_bundles = [];
    $bundle_mapping = $bundle_mapping[$this->entityTypeId];
    foreach ($bundles as $bundle) {
      if (isset($bundle_mapping[$bundle])) {
        $rdf_bundles[] = $bundle_mapping[$bundle];
      }
    }
    return "(<" . implode(">, <", $rdf_bundles) . ">)";
  }

  /**
   * Determine the bundle types for a list of entities.
   */
  protected function getBundlesByIds($ids) {
    $ids_rdf_mapping = array();
    $bundle_mapping = $this->getRdfBundleMapping();
    // @todo Get query through $this->getQuery, and use this wrapper...
    $ids_string = "<" . implode(">, <", $ids) . ">";
    $query = <<<QUERY
SELECT ?uri, ?bundle
WHERE {
  ?uri rdf:type ?bundle.
  FILTER (?uri IN (  $ids_string ))
}
GROUP BY ?uri
QUERY;
    $results = $this->sparql->query($query);
    foreach ($results as $result) {
      $uri = (string) $result->uri;
      $bundle = (string) $result->bundle;
      // @todo Why do we get multiple types for a uri?
      if (array_search($uri, $ids_rdf_mapping)) {
        continue;
      }
      if ($id = array_search($bundle, $bundle_mapping)) {
        $ids_rdf_mapping[$uri] = $id;
      }
      else {
        drupal_set_message(t('Unmapped bundle :bundle for uri :uri.',
          array(
            ':bundle' => $bundle,
            ':uri' => $uri,
          )));
      }

    }
    return $ids_rdf_mapping;
  }

  /**
   * Bundle - label mapping.
   *
   * Get a list of label predicates by bundle.
   */
  public function getLabelMapping() {
    $bundle_label_mapping = array();
    foreach ($this->entityTypeManager->getStorage($this->entityType->getBundleEntityType())
               ->loadMultiple() as $entity) {
      $label = $this->entityType->getKey('label');
      $settings = $entity->getThirdPartySetting('rdf_entity', 'mapping_' . $label, FALSE);
      if (!is_array($settings)) {
        throw new \Exception('No rdf:type mapping set for bundle ' . $entity->label());
      }
      $type = array_pop($settings);
      $bundle_label_mapping[$this->entityTypeId][$type] = $entity->id();
    }
    \Drupal::moduleHandler()->alter('label_mapping', $bundle_label_mapping);
    return $bundle_label_mapping;
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
    foreach ($entities as $id => $entity) {
      $entities_by_graph[$entity->graph][$id] = $entity;
    }
    foreach ($entities_by_graph as $graph => $entities_to_delete) {
      $graph = $entity->graph;
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
    $query->setGraphType();
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
    $graph = $entity->graph;
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
    $bundle = $entity->bundle();
    $rdf_mapping = $this->getRdfBundleMapping();
    $rdf_field = $rdf_mapping[$entity->getEntityTypeId()][$bundle];
    $pred = 'rdf:type';
    $insert .= $subj . ' ' . $pred . ' <' . $rdf_field . '>  .' . "\n";

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

}
