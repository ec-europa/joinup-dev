<?php

namespace Drupal\rdf_entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Contains helper methods that help with the uri mappings of Drupal elements.
 *
 * @package Drupal\rdf_entity
 */
class RdfMappingHandler {
  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a QueryFactory object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *    The entity type manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
    $this->moduleHandler = $this->getModuleHandlerService();
  }

  /**
   * Returns a list of label predicates of the passed entity type.
   *
   * @param string $entity_type_id
   *    The entity type machine name.
   *
   * @return array
   *    An array of label predicates indexed by their respective entity bundles.
   *
   * @throws \Exception
   *    Thrown when an rdf mapping has not been set for a label in one of the
   *    entity bundles.
   * @todo: Especially for properties like label, we can generate it if missing.
   */
  public function getEntityTypeLabelPredicates($entity_type_id) {
    $entity_type = $this->entityManager->getStorage($entity_type_id)->getEntityType();
    $bundle_type = $entity_type->getBundleEntityType();
    $label = $entity_type->getKey('label');
    $bundle_label_mapping = [];
    $bundle_entities = $this->entityManager->getStorage($bundle_type)->loadMultiple();
    foreach ($bundle_entities as $bundle_entity) {
      $settings = $bundle_entity->getThirdPartySetting('rdf_entity', 'mapping_' . $label, FALSE);
      if (!is_array($settings)) {
        throw new \Exception('No label predicate mapping set for bundle ' . $bundle_entity->label());
      }
      $type = array_pop($settings);
      $bundle_label_mapping[$type] = $bundle_entity->id();
    }
    \Drupal::moduleHandler()->alter('label_mapping', $bundle_label_mapping);
    return $bundle_label_mapping;
  }

  /**
   * Returns all bundle key mappings of the passed rdf entity type.
   *
   * These mappings are the actual type of the bundle represented by an rdf
   * URI. This is not the predicate but the object.
   *
   * @param string $entity_type_bundle_key
   *    The machine name of the entity type.
   * @param string $bundle
   *    Optionally filter the mappings by bundle.
   *
   * @return array
   *    A list of bundle key mappings from all bundles of the passed entity
   *    type. The returned array is indexed by the bundle key.
   *
   * @throws \Exception
   *    Thrown when the rdf entity bundle has no mapped type uri.
   */
  public function getRdfBundleMappedUri($entity_type_bundle_key, $bundle = NULL) {
    $bundle_rdf_bundle_mapping = [];
    $storage = $this->entityManager->getStorage($entity_type_bundle_key);

    $bundle_entities = empty($bundle) ? $storage->loadMultiple() : [$storage->load($bundle)];
    foreach ($bundle_entities as $bundle_entity) {
      // The id of the entity type is 'rdf_type' but the key ('id') is the
      // bundle key.
      $bundle_type = $bundle_entity->getEntityType()->getKey('id');
      $settings = $bundle_entity->getThirdPartySetting('rdf_entity', 'mapping_' . $bundle_type, FALSE);
      if (!is_array($settings)) {
        throw new \Exception('No rdf:type mapping set for bundle ' . $bundle_entity->label());
      }
      $type = array_pop($settings);
      $bundle_rdf_bundle_mapping[$bundle_entity->id()] = $type;
    }

    // Allow modules to interact and tamper with the passed list.
    $this->moduleHandler->alter('bundle_mapping', $bundle_rdf_bundle_mapping);
    return $bundle_rdf_bundle_mapping;
  }

  /**
   * Returns a list of bundle uris ready to be passed to a query as an array.
   *
   * @todo: This should return a simple array. A query helper method can convert
   *    it later on.
   *
   * @param string $entity_type_id
   *    The entity type of the bundles e.g. 'node_type'.
   * @param array $bundles
   *    Optionally filter and return only a subset of bundles.
   *
   * @return string
   *    A string including the converted array of bundle uris to a string value
   *    of a sparql array filter.
   */
  public function getBundleUriList($entity_type_id, $bundles = []) {
    $bundle_mapping = $this->getRdfBundleMappedUri($entity_type_id);
    if (empty($bundle_mapping)) {
      return;
    }

    $rdf_bundles = [];
    if (empty($bundles)) {
      $rdf_bundles = array_unique(array_values($bundle_mapping));
    }
    else {
      foreach ($bundles as $bundle) {
        if (isset($bundle_mapping[$bundle])) {
          $rdf_bundles[] = $bundle_mapping[$bundle];
        }
      }
    }

    return "(<" . implode(">, <", $rdf_bundles) . ">)";
  }

  /**
   * Get the mapping between drupal properties and rdf predicates.
   *
   * @todo: We should have a better more generic way of generating these
   *    mappings. E.g., when nothing is set, we can generate automatically for
   *    all properties of the entity and the fields in the following format:
   *    - Entity keys: <base url>/<entity_type_id>/<entity_key>
   *    - Entity field: <base url>/<entity_type_id>/<field_machine_name>
   * /<property>
   *
   * @param string $entity_type_id
   *    The entity type for which the mappings are retrieved.
   *
   * @return array
   *    An array of mappings indexed by bundle.
   */
  public function getEntityPredicates($entity_type_id) {
    $mapping = &drupal_static(__FUNCTION__);
    $storage = $this->entityManager->getStorage($entity_type_id);
    $bundle_type = $storage->getEntityType()->getBundleEntityType();
    // @todo: We can probably get rid of the $entity_type_id index here.
    if (empty($mapping[$entity_type_id])) {
      // Collect entities ids, bundles and languages.
      $rdf_bundle_entities = $this->entityManager->getStorage($bundle_type)->loadMultiple();

      // Collect impacted fields.
      // @todo: remove the entity type id index. Not needed.
      $mapping[$entity_type_id] = [];
      foreach ($rdf_bundle_entities as $rdf_bundle_entity) {
        $base_field_definitions = $this->entityManager->getBaseFieldDefinitions($entity_type_id);
        $field_definitions = $this->entityManager->getFieldDefinitions($entity_type_id, $rdf_bundle_entity->id());
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
            $mapping[$entity_type_id][$rdf_bundle_entity->id()][$predicate] = array(
              'field_name' => $id,
              'column' => $column,
            );
          }
        }
        foreach ($field_definitions as $field_name => $field_definition) {
          $storage_definition = $field_definition->getFieldStorageDefinition();
          if (!$storage_definition instanceof FieldStorageConfig) {
            continue;
          }
          foreach ($storage_definition->getColumns() as $column => $column_info) {
            if ($predicate = $storage_definition->getThirdPartySetting('rdf_entity', 'mapping_' . $column, FALSE)) {
              if (empty($predicate)) {
                continue;
              }
              $mapping[$entity_type_id][$rdf_bundle_entity->id()][$predicate] = array(
                'column' => $column,
                'field_name' => $field_name,
                'storage_definition' => $storage_definition,
              );
            }
          }
        }
      }
    }
    return $mapping[$entity_type_id];
  }

  /**
   * Returns a list of mapped properties for the passed content entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A content entity.
   *
   * @return array
   *    An array of mappings between predicates and field properties. All
   *    fields, and properties of the entity and the fields, that are available
   *    will be returned.
   */
  public function getEntityTypeMappedProperties(EntityInterface $entity) {
    $bundle = $entity->bundle();
    $properties = [];
    // Collect impacted fields.
    $definitions = $this->entityManager->getFieldDefinitions($entity->getEntityTypeId(), $bundle);
    $base_field_definitions = $this->entityManager->getBaseFieldDefinitions($entity->getEntityTypeId());
    $rdf_bundle_entity = $this->entityManager->getStorage($entity->getEntityType()->getBundleEntityType())->load($bundle);
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
   * Returns the module handler service object.
   *
   * @todo: Check how we can inject this.
   */
  protected function getModuleHandlerService() {
    return \Drupal::moduleHandler();
  }
}