<?php

namespace Drupal\rdf_entity;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\rdf_entity\Entity\Query\Sparql\SparqlArg;
use Drupal\rdf_entity\Entity\RdfEntitySparqlStorage;

/**
 * Contains helper methods that help with the uri mappings of Drupal elements.
 *
 * @package Drupal\rdf_entity
 */
class RdfMappingHandler {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a QueryFactory object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->moduleHandler = $this->getModuleHandlerService();
  }

  /**
   * Returns a list of label predicates of the passed entity type.
   *
   * @param string $entity_type_id
   *   The entity type machine name.
   *
   * @return array
   *   An array of label predicates indexed by their respective entity bundles.
   *
   * @throws \Exception
   *    Thrown when an rdf mapping has not been set for a label in one of the
   *    entity bundles.
   *
   * @todo: Especially for properties like label, we can generate it if missing.
   */
  public function getEntityTypeLabelPredicates($entity_type_id) {
    $entity_type = $this->entityTypeManager->getStorage($entity_type_id)->getEntityType();
    $bundle_type = $entity_type->getBundleEntityType();
    $label = $entity_type->getKey('label');
    $bundle_label_mapping = [];
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface[] $bundle_entities */
    $bundle_entities = $this->entityTypeManager->getStorage($bundle_type)->loadMultiple();
    foreach ($bundle_entities as $bundle_entity) {
      $settings = rdf_entity_get_third_party_property($bundle_entity, 'mapping', $label, FALSE);
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
   *   The machine name of the entity type.
   * @param string $bundle
   *   Optionally filter the mappings by bundle.
   *
   * @return array
   *   A list of bundle key mappings from all bundles of the passed entity
   *    type. The returned array is indexed by the bundle key.
   *
   * @throws \Exception
   *    Thrown when the rdf entity bundle has no mapped type uri.
   */
  public function getRdfBundleMappedUri($entity_type_bundle_key, $bundle = NULL) {
    $bundle_rdf_bundle_mapping = &drupal_static(__FUNCTION__);
    if (empty($bundle_rdf_bundle_mapping[$bundle])) {
      $storage = $this->entityTypeManager->getStorage($entity_type_bundle_key);
      $bundle_entities = empty($bundle) ? $storage->loadMultiple() : [$storage->load($bundle)];
      foreach ($bundle_entities as $bundle_entity) {
        // The id of the entity type is 'rdf_type' but the key ('id') is the
        // bundle key.
        $bundle_type = $bundle_entity->getEntityType()->getKey('id');
        $settings = rdf_entity_get_third_party_property($bundle_entity, 'mapping', $bundle_type, FALSE);
        if (!is_array($settings)) {
          throw new \Exception('No rdf:type mapping set for bundle ' . $bundle_entity->label());
        }
        $type = array_pop($settings);
        $bundle_rdf_bundle_mapping[$bundle_entity->id()] = $type;
      }

      // Allow modules to interact and tamper with the passed list.
      $this->moduleHandler->alter('bundle_mapping', $bundle_rdf_bundle_mapping);
    }
    return $bundle_rdf_bundle_mapping;
  }

  /**
   * Get the mapping between drupal properties and rdf predicates.
   *
   * @param string $entity_type_id
   *   The entity type for which the mappings are retrieved.
   *
   * @todo: We should have a better more generic way of generating these
   *    mappings. E.g., when nothing is set, we can generate automatically for
   *    all properties of the entity and the fields in the following format:
   *    - Entity keys: <base url>/<entity_type_id>/<entity_key>
   *    - Entity field: <base url>/<entity_type_id>/<field_machine_name>
   * /<property>
   *
   * @return array
   *   An array of mappings indexed by bundle. The mappings include the base
   *   fields and the additional fields.
   */
  public function getEntityPredicates($entity_type_id) {
    $mapping = &drupal_static(__FUNCTION__);
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $bundle_type = $storage->getEntityType()->getBundleEntityType();
    // @todo: We can probably get rid of the $entity_type_id index here.
    if (empty($mapping[$entity_type_id])) {
      // Collect entities ids, bundles and languages.
      $rdf_bundle_entities = $this->entityTypeManager->getStorage($bundle_type)->loadMultiple();

      // Collect impacted fields.
      // @todo: remove the entity type id index. Not needed.
      $mapping[$entity_type_id] = [];
      foreach ($rdf_bundle_entities as $rdf_bundle_entity) {
        $base_field_definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);
        $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $rdf_bundle_entity->id());
        if (!$base_field_definitions) {
          continue;
        }
        foreach ($base_field_definitions as $id => $base_field_definition) {
          $field_data = rdf_entity_get_third_party_property($rdf_bundle_entity, 'mapping', $id, FALSE);
          if (!$field_data) {
            continue;
          }
          foreach ($field_data as $column => $mapping) {
            if (empty($mapping['predicate'])) {
              continue;
            }
            $mapping[$entity_type_id][$rdf_bundle_entity->id()][$mapping['predicate']] = [
              'field_name' => $id,
              'column' => $column,
              'format' => $mapping['format'],
            ];
          }
        }
        foreach ($field_definitions as $field_name => $field_definition) {
          $storage_definition = $field_definition->getFieldStorageDefinition();
          if (!$storage_definition instanceof FieldStorageConfig) {
            continue;
          }
          foreach ($storage_definition->getColumns() as $column => $column_info) {
            if ($field_data = rdf_entity_get_third_party_property($storage_definition, 'mapping', $column, FALSE)) {
              if (empty($field_data['predicate'])) {
                continue;
              }
              $mapping[$entity_type_id][$rdf_bundle_entity->id()][$field_data['predicate']] = [
                'column' => $column,
                'field_name' => $field_name,
                'format' => $field_data['format'],
                'storage_definition' => $storage_definition,
              ];
            }
          }
        }
      }
    }
    return $mapping[$entity_type_id];
  }

  /**
   * Converts a list of bundle Ids to their corresponding Uris.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param array $values
   *   An array of bundle machine names.
   * @param bool $to_resource_uris
   *   If true, the Ids will be transformed into resource Ids instead.
   *
   * @throws \Exception
   *    Thrown when the bundle does not have a mapping.
   */
  public function bundlesToUris($entity_type_id, array &$values, $to_resource_uris = FALSE) {
    if (SparqlArg::isValidResources($values)) {
      return;
    }
    $bundle_type = $this->entityTypeManager->getStorage($entity_type_id)->getEntityType()->getBundleEntityType();
    $bundle_mappings = $this->getRdfBundleMappedUri($bundle_type);
    foreach ($values as $index => $bundle) {
      if (!isset($bundle_mappings[$bundle])) {
        throw new \Exception("The $bundle bundle does not have a mapping.");
      }
      $values[$index] = $to_resource_uris ? SparqlArg::uri($bundle_mappings[$bundle]) : $bundle_mappings[$bundle];
    }
  }

  /**
   * Returns the rdf mapping of the given property in an entity type.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $field_name
   *   The field machine name.
   *
   * @return array
   *   An array of resource IDs indexed by bundle id.
   *
   * @throws \Exception
   *    Throws an exception when a mapped ID is not found because it means that
   *    either the field name is not part of the entity or that the map has not
   *    been set.
   */
  public function getFieldRdfMapping($entity_type_id, $field_name) {
    $field_mapping = &drupal_static(__FUNCTION__);
    $field_name_parts = explode('.', $field_name);
    $field_name = array_shift($field_name_parts);
    if (!empty($field_name_parts)) {
      $property = array_shift($field_name_parts);
    }
    if (!empty($field_name_parts)) {
      throw new \Exception('SPARQL query: Complex field property selection in unsupported at the moment.');
    }
    if (!isset($field_mapping[$entity_type_id])) {
      $mapping = $this->getEntityPredicates($entity_type_id);
      foreach ($mapping as $bundle_id => $data) {
        foreach ($data as $mapping_id => $field_data) {
          $field_mapping[$entity_type_id][$field_data['field_name']][$field_data['column']][$bundle_id] = SparqlArg::uri($mapping_id);
        }
      }
    }

    if (!isset($field_mapping[$entity_type_id][$field_name])) {
      throw new \Exception("The field $field_name does not appear to have a resource id in the entity type $entity_type_id.");
    }
    if (isset($property)) {
      return $field_mapping[$entity_type_id][$field_name][$property];
    }
    // Fallback to main property.
    $map = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
    $main_property = $map[$field_name]->getMainPropertyName();
    return $field_mapping[$entity_type_id][$field_name][$main_property];
  }

  /**
   * Determines if a field is an entity reference.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $field_name
   *   The field machine name.
   *
   * @return bool
   *   Whether the field is referencing an rdf resource.
   *
   * @throws \Exception
   *   Thrown when the field is not found.
   */
  public function fieldIsRdfReference($entity_type_id, $field_name) {
    $parts = explode('.', $field_name);
    $field_name = reset($parts);
    $base_field_definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);
    if (isset($base_field_definitions[$field_name])) {
      $field_definition = $base_field_definitions[$field_name]->getItemDefinition();
    }
    else {
      $field_definition = $this->entityTypeManager->getStorage('field_storage_config')->load($entity_type_id . '.' . $field_name);
    }

    if (empty($field_definition)) {
      throw new \Exception("The field $field_name was not found.");
    }
    $target_type = $field_definition->getSetting('target_type');
    if (empty($target_type)) {
      return FALSE;
    }
    $target_entity_storage_class = $this->entityTypeManager->getStorage($target_type);
    return ($target_entity_storage_class instanceof RdfEntitySparqlStorage) || is_subclass_of($target_entity_storage_class, RdfEntitySparqlStorage::class);
  }

  /**
   * Returns a list of mapped properties for the passed content entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A content entity.
   *
   * @return array
   *   An array of mappings between predicates and field properties. All
   *    fields, and properties of the entity and the fields, that are available
   *    will be returned.
   */
  public function getEntityTypeMappedProperties(EntityInterface $entity) {
    $bundle = $entity->bundle();
    $properties = [];
    // Collect impacted fields.
    $definitions = $this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $bundle);
    $base_field_definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity->getEntityTypeId());
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $rdf_bundle_entity */
    $rdf_bundle_entity = $this->entityTypeManager->getStorage($entity->getEntityType()->getBundleEntityType())->load($bundle);
    /** @var \Drupal\Core\Field\BaseFieldDefinition $field_definition */
    foreach ($definitions as $field_name => $field_definition) {
      /** @var \Drupal\field\Entity\FieldStorageConfig $storage_definition */
      $storage_definition = $field_definition->getFieldStorageDefinition();
      if (!$storage_definition instanceof FieldStorageConfig) {
        continue;
      }
      foreach ($storage_definition->getColumns() as $column => $column_info) {
        if ($property = rdf_entity_get_third_party_property($storage_definition, 'mapping', $column, FALSE)) {
          $properties['by_field'][$field_name][$column] = $property['predicate'];
          $properties['flat'][$property['predicate']] = $property['predicate'];
        }
      }
    }
    foreach ($base_field_definitions as $field_name => $base_field_definition) {
      $field_data = rdf_entity_get_third_party_property($rdf_bundle_entity, 'mapping', $field_name, FALSE);
      if (!$field_data) {
        continue;
      }
      foreach ($field_data as $column => $predicate) {
        if (empty($property['predicate'])) {
          continue;
        }
        $properties['by_field'][$field_name][$column] = $property['predicate'];
        $properties['flat'][$property['predicate']] = $property['predicate'];
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
