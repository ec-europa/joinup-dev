<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\ContentEntityNullStorage.
 */

namespace Drupal\rdf_entity\Entity;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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

  /**
   * Initialize the storage backend.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $sparql, EntityManagerInterface $entity_manager, EntityTypeManagerInterface $entity_type_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager) {
    parent::__construct($entity_type, $entity_manager, $cache);
    $this->sparql = $sparql;
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('language_manager')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function doLoadMultiple(array $ids = NULL) {
    // Attempt to load entities from the persistent cache. This will remove IDs
    // that were loaded from $ids.
    $entities_from_cache = $this->getFromPersistentCache($ids);

    // Load any remaining entities from the database.
    if ($entities_from_storage = $this->getFromStorage($ids)) {
      $this->invokeStorageLoadHook($entities_from_storage);
      $this->setPersistentCache($entities_from_storage);
    }

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
    $entities = array();
    $values = array();
    $bundles = $this->getBundlesByIds($ids);
    foreach ($ids as $id) {
      $values[$id] = array(
        'rid' => array('x-default' => $bundles[$id]),
        'id' => array('x-default' => $id),
      );
    }
    $this->loadFromBaseTable($values);
    $this->loadFromDedicatedTables($values, FALSE);
    foreach ($values as $id => $entity_values) {
      $entity = new Rdf($entity_values, 'rdf_entity', $bundles[$id]);
      $entities[$id] = $entity;
    }
    return $entities;
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
    foreach ($this->entityTypeManager->getStorage('rdf_type')->loadMultiple() as $entity) {
      $bundle_rdf_bundle_mapping[$entity->rdftype] = $entity->id();
    }
    return $bundle_rdf_bundle_mapping;
  }

  /**
   * Returns an rdf object for each bundle.
   *
   * Returns the rdf object that is specific for this bundle.
   */
  public function getRdfBundleList($bundles) {
    if (!$bundles) {
      return;
    }

    $bundle_mapping = $this->getRdfBundleMapping();
    if (empty($bundle_mapping)) {
      return;
    }
    $rdf_bundels = [];
    $bundle_mapping = array_flip($bundle_mapping);
    foreach ($bundles as $bundle) {
      $rdf_bundels[] = $bundle_mapping[$bundle];
    }
    return "(<" . implode(">, <", $rdf_bundels) . ">)";
  }

  /**
   * Bundle - label mapping.
   *
   * Get a list of label predicates by bundle.
   */
  public function getLabelMapping() {
    $bundle_label_mapping = array();
    foreach ($this->entityTypeManager->getStorage('rdf_type')->loadMultiple() as $entity) {
      $label_field = $entity->get('rdf_label');
      if (!$label_field) {
        continue;
      }
      $bundle_label_mapping[$entity->id()] = $label_field;
    }
    return $bundle_label_mapping;
  }

  /**
   * Fetch a list of triple predicates for labels.
   *
   * @return string
   *   String of label predicates, suitable for inclusion in a query.
   */
  public function getRdfLabelList($bundles) {
    if (!$bundles) {
      return;
    }

    $label_mapping = $this->getLabelMapping();
    if (empty($label_mapping)) {
      return;
    }
    $labels = [];
    foreach ($bundles as $bundle) {
      $labels[] = $label_mapping[$bundle];
    }
    return "(<" . implode(">, <", $labels) . ">)";
  }

  /**
   * Determine the bundle types for a list of entities.
   */
  protected function getBundlesByIds($ids) {
    $ids_rdf_mapping = array();
    $bundle_mapping = $this->getRdfBundleMapping();
    $ids_string = "<" . implode(">, <", $ids) . ">";
    $query
      = 'SELECT ?uri, ?bundle
WHERE {
  ?uri rdf:type ?bundle.
  FILTER (?uri IN ( ' . $ids_string . '))
}
GROUP BY ?uri';
    $results = $this->sparql->query($query);
    foreach ($results as $result) {
      $uri = (string) $result->uri;
      $bundle = (string) $result->bundle;
      // @todo Why do we get multiple types for a uri?
      if (isset($ids_rdf_mapping[$uri])) {
        continue;
      }
      if (isset($bundle_mapping[$bundle])) {
        $ids_rdf_mapping[$uri] = $bundle_mapping[$bundle];
      }
      else {
        drupal_set_message('unmapped bundle ' . $bundle . ' for uri ' . $uri);
      }

    }
    return $ids_rdf_mapping;
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
  public function loadByProperties(array $values = array()) {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $entities) {
  }

  /**
   * {@inheritdoc}
   */
  protected function doDelete($entities) {
  }

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $entity) {
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
    $query = \Drupal::service($this->getQueryServiceName())->get($this->entityType, $conjunction);
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
   * {@inheritdoc}
   */
  protected function doSave($id, EntityInterface $entity) {
  }

  /**
   * {@inheritdoc}
   */
  protected function has($id, EntityInterface $entity) {
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
   * Loads values of fields stored in dedicated tables for a group of entities.
   *
   * @param array &$values
   *   An array of values keyed by entity ID.
   */
  protected function loadFromBaseTable(array &$values) {
    // The label field is bundle specific, so determine the field to use first.
    $label = $this->getLabelMapping();
    $ids_by_label = array();
    foreach ($values as $entity_id => $entity_values) {
      $bundle = $entity_values['rid'][LanguageInterface::LANGCODE_DEFAULT];
      if (isset($label[$bundle])) {
        $ids_by_label[$label[$bundle]][] = $entity_id;
      }
      $values[$entity_id]['label'][LanguageInterface::LANGCODE_DEFAULT] = $entity_id;
    }
    foreach ($ids_by_label as $label => $ids) {
      $ids_string = "<" . implode(">, <", $ids) . ">";
      $query
        = 'SELECT ?uri ?label
          WHERE{
          ?uri <' . $label . '> ?label
          FILTER (?uri IN ( ' . $ids_string . '))
          }';
      /** @var \EasyRdf_Sparql_Result $results */
      $results = $this->sparql->query($query);
      foreach ($results as $result) {
        $uri = (string) $result->uri;
        $label = (string) $result->label;
        $values[$uri]['label'][LanguageInterface::LANGCODE_DEFAULT] = $label;
      }
    }
  }

  /**
   * Loads values of fields stored in dedicated tables for a group of entities.
   *
   * @param array &$values
   *   An array of values keyed by entity ID.
   * @param bool $load_from_revision
   *   (optional) Flag to indicate whether revisions should be loaded or not,
   *   defaults to FALSE.
   */
  protected function loadFromDedicatedTables(array &$values, $load_from_revision) {
    // Collect entities ids, bundles and languages.
    $bundles = array();
    $ids = array();
    $default_langcodes = array();
    foreach ($values as $key => $entity_values) {
      $bundles[$this->bundleKey ? $entity_values['rid'][LanguageInterface::LANGCODE_DEFAULT] : $this->entityTypeId] = TRUE;
      $ids[] = !$load_from_revision ? $key : $entity_values[$this->revisionKey][LanguageInterface::LANGCODE_DEFAULT];
      if ($this->langcodeKey && isset($entity_values[$this->langcodeKey][LanguageInterface::LANGCODE_DEFAULT])) {
        $default_langcodes[$key] = $entity_values[$this->langcodeKey][LanguageInterface::LANGCODE_DEFAULT];
      }
    }

    // Collect impacted fields.
    $storage_definitions = array();
    $definitions = array();
    foreach ($bundles as $bundle => $v) {
      $definitions[$bundle] = $this->entityManager->getFieldDefinitions($this->entityTypeId, $bundle);
      foreach ($definitions[$bundle] as $field_name => $field_definition) {
        /** @var \Drupal\field\Entity\FieldStorageConfig $storage_definition */
        $storage_definition = $field_definition->getFieldStorageDefinition();
        $storage_definitions[$field_name] = $storage_definition;
      }
    }
    // Load field data.
    $tables = [];
    foreach ($storage_definitions as $field_name => $storage_definition) {
      if (!$storage_definition instanceof \Drupal\field\Entity\FieldStorageConfig) {
        continue;
      }

      foreach ($storage_definition->getColumns() as $column => $column_info) {
        if ($table = $storage_definition->getThirdPartySetting('rdf_entity', 'mapping_' . $column, FALSE)) {
          $tables[$table] = array(
            'column' => $column,
            'field_name' => $field_name,
            'storage_definition' => $storage_definition,
          );
        }
      }
    }

    $ids_string = "<" . implode(">, <", $ids) . ">";
    $tables_string = "<" . implode(">, <", array_keys($tables)) . ">";

    $query
      = 'SELECT ?entity_id ?table ?field_value
          WHERE{
          ?entity_id ?table ?field_value
          FILTER (?table IN ( ' . $tables_string . '))
          FILTER (?entity_id IN ( ' . $ids_string . '))
          }';
    /** @var \EasyRdf_Sparql_Result $results */
    $results = $this->sparql->query($query);
    foreach ($results as $result) {
      $entity_id = (string) $result->entity_id;
      $table = (string) $result->table;
      $field_value = (string) $result->field_value;
      $field_name = $tables[$table]['field_name'];
      $column = $tables[$table]['column'];
      $values[$entity_id][$field_name][LanguageInterface::LANGCODE_DEFAULT][][$column] = $field_value;
      $storage_definition = $tables[$table]['storage_definition'];
      $this->applyFieldDefaults($storage_definition, $values[$entity_id][$storage_definition->getName()][LanguageInterface::LANGCODE_DEFAULT]);
    }
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
  }

}
