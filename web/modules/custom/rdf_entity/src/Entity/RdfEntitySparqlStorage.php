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
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a null entity storage.
 *
 * Used for content entity types that have no storage.
 */
class RdfEntitySparqlStorage extends ContentEntityStorageBase {

  public function __construct(EntityTypeInterface $entity_type, Connection $sparql, EntityManagerInterface $entity_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager) {
    parent::__construct($entity_type, $entity_manager, $cache);
    $this->sparql = $sparql;
    $this->languageManager = $language_manager;
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
      $container->get('cache.entity'),
      $container->get('language_manager')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = NULL) {
    // @todo Rewrite to use ids.
    $results = $this->sparql->query(
      'SELECT ?entity ?lang ?desc ' .
      'WHERE{' .
        '?entity rdf:type admssw:SoftwareProject.'.
        '?entity admssw:programmingLanguage ?lang.'.
        '?entity dct:description ?desc'.
      '} LIMIT 50'
    );
    $entities = array();
    $values = array();

    foreach ($results as $result) {
      $values[] = array(
        'rid' => array('x-default' => 'admssw_softwareproject'),
        'id' => array('x-default' => str_replace('/', '\\' ,(string) $result->entity)),
        //'id' => array('x-default' => $i),
        'name' => array('x-default' => str_replace('/', '\\' ,(string) $result->entity)),
        // 'name' => array('x-default' => (string) $result->lang),
        'first_name' => array('x-default' => (string) $result->desc),
        'gender' => array('x-default' => 'male'),
        'user_id' => array('x-default' => 1),
        'langcode' => array('x-default' => 'und'),
      );
      $this->loadFromDedicatedTables($values, FALSE);

    }
    foreach ($values as $entity_values) {
      $entity = new Rdf($entity_values, 'rdf_entity', 'admssw_softwareproject');
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
    $id = str_replace('\\', '/', $id_sanitized);
    $sparql = new \EasyRdf_Sparql_Client('http://localhost:8890/sparql');
    $results = $sparql->query(
      "SELECT ?lang ?desc " .
      'WHERE{' .
      "<$id> admssw:programmingLanguage ?lang.".
      "<$id> dct:description ?desc".
      '} LIMIT 1'
    );
    $values = NULL;
    foreach ($results as $result) {
      $bundle = $this->getBundlebyId($id);
      $values[] = array(
        'rid' => array('x-default' => 'admssw_softwareproject'),
        'id' => array('x-default' => $id_sanitized),
        'name' => array('x-default' => (string) $result->lang),
        'first_name' => array('x-default' => (string) $result->desc),
        'gender' => array('x-default' => 'male'),
        'user_id' => array('x-default' => 1),
        'langcode' => array('x-default' => 'und'),
      );
      $this->loadFromDedicatedTables($values, FALSE);
    }
    foreach ($values as $entity_values) {
      return new Rdf($entity_values, 'rdf_entity', 'admssw_softwareproject');
    }
  }

  protected function getBundlebyId($id) {
    $query =
    'SELECT ?bundle
      WHERE{
      <' . $id . '> rdf:type ?bundle.
    } LIMIT 1';

    $results = $this->sparql->query($query);

    $values = array(
      'targetEntityType' => 'rdf_entity',
      'bundle' => 'admssw_softwareproject',
    );

    $entity_type = 'rdf_mapping';
    // $bundles = new RdfEntityType($values, $entity_type);
    $rdf_bundles = $this->entityManager->getBundleInfo('rdf_entity');

    foreach ($results as $result) {
      $rdf_bundle = (string) $result->bundle;
    }
    // Look in bundle settings for matching bundle.
    foreach ($rdf_bundles as $bundle => $bundle_info) {

      if ($bundle_info['label'] == $rdf_bundle) {
        return $bundle;
      }
    }
    // throw new EntityMalformedException(t('Unable to match bundle.'));
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
      foreach ($values as $entity_id => &$entity_values) {
        // @todo Get this through the service container.
        $query =
          'SELECT ?field_value ' .
          'WHERE{' .
          '<' . str_replace('\\', '/', $entity_values['id'][LanguageInterface::LANGCODE_DEFAULT]) . '> <' . $table . '>  ?field_value'.
          '} LIMIT 50';
        /** @var \EasyRdf_Sparql_Result $results */
        $results = $this->sparql->query($query);
        foreach ($results as $result) {
          $field_value = (string) $result->field_value;
          $entity_values[$field_name][LanguageInterface::LANGCODE_DEFAULT][] = $field_value;
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
