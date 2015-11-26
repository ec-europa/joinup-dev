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
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a entity storage backend that uses a Sparql endpoint.
 */
class RdfEntitySparqlStorage extends ContentEntityStorageBase {

  public function __construct(EntityTypeInterface $entity_type, Connection $sparql, EntityManagerInterface $entity_manager, EntityTypeManagerInterface $entity_type_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager) {
    parent::__construct($entity_type, $entity_manager, $cache);
    $this->sparql = $sparql;
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
    // $this->initTableLayout();
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
      $safe_id = str_replace('/', '\\' ,(string) $id);
      $values[$id] = array(
        'rid' => array('x-default' => $bundles[$id]),
        'id' => array('x-default' => $safe_id),
      );
      $this->loadFromDedicatedTables($values, FALSE);
    }
    foreach ($values as $id => $entity_values) {
      $entity = new Rdf($entity_values, 'rdf_entity', $bundles[$id]);
      $entities[] = $entity;
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
    $id = str_replace('\\', '/', $id_sanitized);
    $bundles = $this->getBundlesByIds(array($id));
    $bundle = $bundles[$id];
    $values = array(
      $id => array(
        'rid' => array('x-default' => $bundle),
        'id' => array('x-default' => $id_sanitized),
      ),
    );
    $this->loadFromDedicatedTables($values, FALSE);
    foreach ($values as $entity_values) {
      $entity = new Rdf($entity_values, 'rdf_entity', $bundle);
      return $entity;
    }
  }

  protected function getRdfBundleMapping() {
    $bundle_rdf_bundle_mapping = array();
    foreach ($this->entityTypeManager->getStorage('rdf_type')->loadMultiple() as $entity) {
      $bundle_rdf_bundle_mapping[$entity->rdftype] = $entity->id();
    }
    return $bundle_rdf_bundle_mapping;
  }

  protected function getBundlesByIds($ids) {
    $bundle_mapping = $this->getRdfBundleMapping();

    $ids_rdf_mapping = array();
    foreach ($ids as $id) {
      // @todo Optimize this to do ONE query (move out foreach).
      $query =
        'SELECT ?bundle
        WHERE{
          <' . $id . '> rdf:type ?bundle.
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
        //throw new EntityMalformedException('Id has no corresponding Drupal bundle.');
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
      $table = $storage_definition->getThirdPartySetting('rdf_entity', 'rdf_mapping', FALSE);
      if (!$table) {
        continue;
      }
      // @todo Optimize for speed later. This is really not the way to go, but let's start somewhere.
      // This is where I should slap myself in the face, as it will melt the triplestore.
      foreach ($values as $entity_id => $entity_values) {
        $query =
          'SELECT ?field_value ' .
          'WHERE{' .
          '<' . $entity_id . '> <' . $table . '>  ?field_value'.
          '} LIMIT 50';
        /** @var \EasyRdf_Sparql_Result $results */
        $results = $this->sparql->query($query);
        $values[$entity_id][$field_name][LanguageInterface::LANGCODE_DEFAULT] = array();
        foreach ($results as $result) {
          $field_value = (string) $result->field_value;
          $values[$entity_id][$field_name][LanguageInterface::LANGCODE_DEFAULT][] = $field_value;
        }
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
}
