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
use Drupal\Core\Entity\EntityMalformedException;
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
  public function loadMultiple(array $ids = NULL) {
    $entities = array();
    $values = array();
    $bundles = $this->getBundlesByIds($ids);
    foreach ($ids as $id) {
      $safe_id = str_replace('/', '\\', (string) $id);
      $values[$id] = array(
        'rid' => array('x-default' => $bundles[$id]),
        'id' => array('x-default' => $safe_id),
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
  protected function doLoadMultiple(array $ids = NULL) {
  }

  /**
   * {@inheritdoc}
   */
  public function load($id_sanitized) {
    // @todo Write a route handler to inject a proper id here.
    // @see https://www.drupal.org/node/2310427
    $id = str_replace('\\', '/', $id_sanitized);
    $entities = $this->loadMultiple(array($id));
    return array_shift($entities);
  }

  /**
   * Get the mapping between bundle names and their rdf properties.
   */
  protected function getRdfBundleMapping() {
    $bundle_rdf_bundle_mapping = array();
    foreach ($this->entityTypeManager->getStorage('rdf_type')->loadMultiple() as $entity) {
      $bundle_rdf_bundle_mapping[$entity->rdftype] = $entity->id();
    }
    return $bundle_rdf_bundle_mapping;
  }

  /**
   * Determine the bundle types for a list of entities.
   */
  protected function getBundlesByIds($ids) {
    $bundle_mapping = $this->getRdfBundleMapping();

    $ids_rdf_mapping = array();
    foreach ($ids as $id) {
      // @todo Optimize this to do ONE query (move out foreach).
      $query
        = 'SELECT ?bundle
        WHERE{
          <' . $id . '> rdf:type ?bdl.
          ?bdl <http://purl.org/dc/terms/identifier> ?bundle.
        } LIMIT 1';
      $results = $this->sparql->query($query);
      $results = $results->getArrayCopy();
      if (is_array($results)) {
        $result = array_shift($results);
      }
      elseif (is_object($results)) {
        $result = $results;
      }
      else {
        throw new EntityMalformedException('Unable to query bundle type from Sparql endpoint.');
      }
      $rdf_bundle = (string) $result->bundle;
      if (isset($bundle_mapping[$rdf_bundle])) {
        $ids_rdf_mapping[$id] = $bundle_mapping[$rdf_bundle];
      }
      else {
        $ids_rdf_mapping[$id] = 'unknown_bundle: ' . $rdf_bundle;
        // @todo Throw new EntityMalformedException
        // ('Id has no corresponding Drupal bundle.');.
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
    // @todo Find a way to move query out of loop.
    foreach ($values as $entity_id => $entity_values) {
      // @todo: This doesn't feel right... All titles should be in one field.
      $query
        = 'SELECT ?label
        WHERE{
        {<' . $entity_id . '> <http://www.w3.org/2000/01/rdf-schema#label>  ?label.}
        UNION
        { <' . $entity_id . '> <http://usefulinc.com/ns/doap#name> ?label. }
        UNION
        { <' . $entity_id . '> <http://purl.org/dc/terms/title> ?label. }
        } LIMIT 1';
      /** @var \EasyRdf_Sparql_Result $results */
      $results = $this->sparql->query($query);
      $results = $results->getArrayCopy();
      if (is_array($results)) {
        $result = array_shift($results);
      }
      elseif (is_object($results)) {
        $result = $results;
      }
      else {
        throw new EntityMalformedException('Unable to query bundle type from Sparql endpoint.');
      }
      $label = (string) $result->label;
      if ($label) {
        $values[$entity_id]['label'][LanguageInterface::LANGCODE_DEFAULT] = $label;
      }
      else {
        $values[$entity_id]['label'][LanguageInterface::LANGCODE_DEFAULT] = $entity_id;
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
    // $table_mapping = $this->getTableMapping();
    $table_mapping = array();
    foreach ($bundles as $bundle => $v) {
      $definitions[$bundle] = $this->entityManager->getFieldDefinitions($this->entityTypeId, $bundle);
      foreach ($definitions[$bundle] as $field_name => $field_definition) {
        /** @var \Drupal\field\Entity\FieldStorageConfig $storage_definition */
        $storage_definition = $field_definition->getFieldStorageDefinition();
        $storage_definitions[$field_name] = $storage_definition;
      }
    }
    // Load field data.
    foreach ($storage_definitions as $field_name => $storage_definition) {
      if (!$storage_definition instanceof \Drupal\field\Entity\FieldStorageConfig) {
        continue;
      }
      $tables = [];
      foreach ($storage_definition->getColumns() as $column => $column_info) {
        if ($table = $storage_definition->getThirdPartySetting('rdf_entity', 'mapping_' . $column, FALSE)) {
          $tables[$column] = $table;
        }
      }
      if (!$tables) {
        continue;
      }
      // @todo Optimize for speed later. This is really not the way to go, but let's start somewhere.
      // This is where I should slap myself in the face,
      // as it will melt the triplestore.
      foreach ($values as $entity_id => $entity_values) {
        foreach ($tables as $column => $table) {
          if (!filter_var($table, FILTER_VALIDATE_URL) === FALSE) {
            $table = '<' . $table . '>';
          }
          $query
            = 'SELECT ?field_value
          WHERE{
          <' . $entity_id . '> ' . $table . ' ?field_value
          } LIMIT 50';
          /** @var \EasyRdf_Sparql_Result $results */
          $results = $this->sparql->query($query);

          $i = 0;
          foreach ($results as $result) {
            $field_value = (string) $result->field_value;
            $values[$entity_id][$field_name][LanguageInterface::LANGCODE_DEFAULT][$i][$column] = $field_value;
            $i++;
          }
        }
        $this->applyFieldDefaults($storage_definition, $values[$entity_id][$storage_definition->getName()][LanguageInterface::LANGCODE_DEFAULT]);
      }
      $results = array();

      foreach ($results as $row) {
        $bundle = $row->bundle;

        // Field values in default language are stored with
        // LanguageInterface::LANGCODE_DEFAULT as key.
        $langcode = LanguageInterface::LANGCODE_DEFAULT;
        if ($this->langcodeKey && isset($default_langcodes[$row->entity_id]) && $row->langcode != $default_langcodes[$row->entity_id]) {
          $langcode = $row->langcode;
        }

        if (!isset($values[$row->entity_id][$field_name][$langcode])) {
          $values[$row->entity_id][$field_name][$langcode] = array();
        }

        // Ensure that records for non-translatable fields having invalid
        // languages are skipped.
        if ($langcode == LanguageInterface::LANGCODE_DEFAULT || $definitions[$bundle][$field_name]->isTranslatable()) {
          if ($storage_definition->getCardinality() == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED || count($values[$row->entity_id][$field_name][$langcode]) < $storage_definition->getCardinality()) {
            $item = array();
            // For each column declared by the field, populate the item from the
            // prefixed database column.
            foreach ($storage_definition->getColumns() as $column => $attributes) {
              $column_name = $table_mapping->getFieldColumnName($storage_definition, $column);
              // Unserialize the value if specified in the column schema.
              $item[$column] = (!empty($attributes['serialize'])) ? unserialize($row->$column_name) : $row->$column_name;
            }

            // Add the item to the field values for the entity.
            $values[$row->entity_id][$field_name][$langcode][] = $item;
          }
        }
      }
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
