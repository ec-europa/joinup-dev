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
   * A drupal oriented property mapping array.
   *
   * @todo: More information on the structure?
   *
   * @var array
   */
  protected $drupalToSparql;

  /**
   * A SPARQL oriented property mapping array.
   *
   * @todo: More information on the structure?
   *
   * @var array
   */
  protected $sparqlToDrupal;

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
  }

  /**
   * Prepares the property mappings for the given entity type id.
   *
   * @param string $entity_type_id
   *    The entity type id.
   */
  protected function buildEntityTypeProperties($entity_type_id) {
    if (empty($mapping[$entity_type_id]) && empty($mapping[$entity_type_id])) {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      $bundle_type = $storage->getEntityType()->getBundleEntityType();
      $bundle_storage = $this->entityTypeManager->getStorage($bundle_type);
      $this->drupalToSparql[$entity_type_id] = $this->sparqlToDrupal[$entity_type_id] = [];
      $this->drupalToSparql[$entity_type_id]['bundle_key'] = $this->sparqlToDrupal[$entity_type_id]['bundle_key'] = $bundle_storage->getEntityType()->getKey('id');
      $rdf_bundle_entities = $this->entityTypeManager->getStorage($bundle_type)->loadMultiple();
      $this->drupalToSparql[$entity_type_id]['bundles'] = array_keys($rdf_bundle_entities);

      $field_definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);
      foreach ($rdf_bundle_entities as $rdf_bundle_entity) {
        foreach ($field_definitions as $id => $base_field_definition) {
          $field_data = rdf_entity_get_third_party_property($rdf_bundle_entity, 'mapping', $id, FALSE);
          if (!$field_data) {
            continue;
          }
          foreach ($field_data as $column => $column_info) {
            if (empty($column_info['predicate'])) {
              continue;
            }

            $this->drupalToSparql[$entity_type_id]['fields'][$id]['main_property'] = $base_field_definition->getFieldStorageDefinition()->getMainPropertyName();
            $this->drupalToSparql[$entity_type_id]['fields'][$id]['columns'][$column][$rdf_bundle_entity->id()] = [
              'mapping' => $column_info['predicate'],
              'format' => $column_info['format'],
            ];

            $this->sparqlToDrupal[$entity_type_id]['fields'][$column_info['predicate']][$rdf_bundle_entity->id()] = $id;
          }
        }
      }

      foreach ($rdf_bundle_entities as $rdf_bundle_entity) {
        $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $rdf_bundle_entity->id());
        foreach ($field_definitions as $id => $base_field_definition) {
          $storage = $base_field_definition->getFieldStorageDefinition();
          $field_data = rdf_entity_get_third_party_property($storage, 'mapping', $id, FALSE);
          if (!$field_data) {
            continue;
          }
          foreach ($field_data as $column => $column_info) {
            if (empty($column_info['predicate'])) {
              continue;
            }

            $this->drupalToSparql[$entity_type_id]['fields'][$id]['main_property'] = $storage->getMainPropertyName();
            $this->drupalToSparql[$entity_type_id]['fields'][$id]['columns'][$column][$rdf_bundle_entity->id()] = [
              'mapping' => $column_info['predicate'],
              'format' => $column_info['format'],
            ];
            $this->sparqlToDrupal[$entity_type_id]['fields'][$column_info['predicate']][$rdf_bundle_entity->id()] = $id;
          }
        }
      }
    }
  }

  /**
   * Checks if the drupalToSparql array after checking if it needs build.
   *
   * @param string $entity_type_id
   *    The entity type id.
   *
   * @return array
   *    The drupalToSparql array.
   */
  public function getDrupalToSparql($entity_type_id): array {
    if (!isset($this->drupalToSparql[$entity_type_id])) {
      $this->buildEntityTypeProperties($entity_type_id);
    }
    return $this->drupalToSparql[$entity_type_id];
  }

  /**
   * Checks if the sparqlToDrupal array after checking if it needs build.
   *
   * @param string $entity_type_id
   *    The entity type id.
   *
   * @return array
   *    The sparqlToDrupal array.
   */
  public function getSparqlToDrupal($entity_type_id): array {
    if (!isset($this->sparqlToDrupal[$entity_type_id])) {
      $this->buildEntityTypeProperties($entity_type_id);
    }
    return $this->sparqlToDrupal[$entity_type_id];
  }

  /**
   * Returns the predicates for a given field.
   *
   * @param string $entity_type_id
   *    The entity type id.
   * @param string $field
   *    The field name.
   * @param string $column
   *    The column name. If empty, the main property will be used instead.
   * @param string $bundle
   *    Optionally filter the final array by bundle.
   *
   * @return array
   *    An array of predicates.
   *
   * @throws \Exception
   *    Thrown when a non existing field is requested.
   */
  public function getFieldPredicates($entity_type_id, $field, $column = NULL, $bundle = NULL) {
    $drupal_to_sparql = $this->getDrupalToSparql($entity_type_id);
    if (!isset($drupal_to_sparql['fields'][$field])) {
      throw new \Exception("You are requesting the mapping for a non mapped field: $field.");
    }
    $field_mapping = $drupal_to_sparql['fields'][$field];
    $column = $column ?: $field_mapping['main_property'];

    $bundles = $bundle ? [$bundle] : $drupal_to_sparql['bundles'];
    $return = [];
    foreach ($bundles as $bundle) {
      $return[$bundle] = $field_mapping['columns'][$column][$bundle]['predicate'];
    }
    return $return;
  }

  /**
   * Returns the format for a given field.
   *
   * The format
   *
   * @param string $entity_type_id
   *    The entity type id.
   * @param string $field
   *    The field name.
   * @param string $column
   *    The column name. If empty, the main property will be used instead.
   * @param string $bundle
   *    Optionally filter the final array by bundle.
   *
   * @return array
   *    An array of predicates.
   *
   * @throws \Exception
   *    Thrown when a non existing field is requested.
   */
  public function getFieldFormat($entity_type_id, $field, $column = NULL, $bundle = NULL) {
    $drupal_to_sparql = $this->getDrupalToSparql($entity_type_id);
    if (!isset($drupal_to_sparql['fields'][$field])) {
      throw new \Exception("You are requesting the mapping for a non mapped field: $field.");
    }
    $field_mapping = $drupal_to_sparql['fields'][$field];
    $column = $column ?: $field_mapping['main_property'];

    if (!empty($bundle)) {
      return [$field_mapping['columns'][$column][$bundle]['format']];
    }

    return array_values(array_column($field_mapping['columns'][$column], 'format'));
  }

  /**
   * Returns a list of label predicates of the passed entity type.
   *
   * @param string $entity_type_id
   *   The entity type machine name.
   * @param string $key
   *   The entity key to return.
   * @param string $bundle
   *   Optionally filter by bundle.
   *
   * @return array
   *   An array of label predicates indexed by their respective entity bundles.
   *
   * @throws \Exception
   *    Thrown when an rdf mapping has not been set for a label in one of the
   *    entity bundles.
   */
  protected function getEntityTypeKeyPredicates($entity_type_id, $key, $bundle = NULL) {
    $entity_type = $this->entityTypeManager->getStorage($entity_type_id)->getEntityType();
    if (!in_array($key, array_keys($entity_type->getKeys()))) {
      throw new \Exception("The requested entity key was not found in the entity type.");
    }

    $key = $entity_type->getKey($key);
    return $this->getFieldPredicates($entity_type_id, $key, NULL, $bundle);
  }

  /**
   * Returns a list of label predicates of the passed entity type.
   *
   * @param string $entity_type_id
   *   The entity type machine name.
   *
   * @return array
   *   An array of label predicates indexed by their respective entity bundles.
   */
  public function getEntityTypeLabelPredicates($entity_type_id) {
    return $this->getEntityTypeKeyPredicates($entity_type_id, 'label');
  }

  /**
   * Returns all bundle key mappings of the passed rdf entity type.
   *
   * These mappings are the actual type of the bundle represented by an rdf
   * URI. This is not the predicate but the object.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   Optionally filter the mappings by bundle.
   *
   * @return array
   *   A list of bundle key mappings from all bundles of the passed entity
   *    type. The returned array is indexed by the bundle key.
   *
   * @throws \Exception
   *    Thrown when the rdf entity bundle has no mapped type uri.
   *
   * @deprecated To be removed and replaced by getEntityTypeBundlePredicates().
   */
  public function getRdfBundleMappedUri($entity_type_id, $bundle = NULL) {
    return $this->getEntityTypeBundlePredicates($entity_type_id, $bundle);
  }

  /**
   * Returns all bundle key mappings of the passed rdf entity type.
   *
   * These mappings are the actual type of the bundle represented by an rdf
   * URI. This is not the predicate but the object.
   *
   * @param string $entity_type_id
   *   The entity type id.
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
  public function getEntityTypeBundlePredicates($entity_type_id, $bundle = NULL) {
    return $this->getEntityTypeKeyPredicates($entity_type_id, 'bundle', $bundle);
  }

  /**
   * Get the mapping between drupal properties and rdf predicates.
   *
   * @return array
   *    The mapped properties.
   *
   * @deprecated To be replaced by getDrupalToSparql
   */
  public function getEntityPredicates($entity_type_id) {
    return $this->getDrupalToSparql($entity_type_id);
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
   *
   * @deprecated To be completely replaced by getFieldPredicates
   */
  public function getFieldRdfMapping($entity_type_id, $field_name) {
    return $this->getFieldPredicates($entity_type_id, $field_name);
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
   */
  public function fieldIsResource($entity_type_id, $field_name) {
    $format = $this->getFieldFormat($entity_type_id, $field_name);
    // The type of the field should not be different between bundles.
    $format = reset($format);
    return $format === 'resource';
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
   *
   * @deprecated To be replaced by fieldIsResource().
   */
  public function fieldIsRdfReference($entity_type_id, $field_name) {
    return $this->fieldIsResource($entity_type_id, $field_name);
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
   *
   * @deprecated To be replaced by getDrupalToSparql.
   */
  public function getEntityTypeMappedProperties(EntityInterface $entity) {
    return $this->getDrupalToSparql($entity->id());
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
   * @return array
   *    The altered array.
   *
   * @throws \Exception
   *    Thrown when the bundle does not have a mapping.
   */
  public function bundlesToUris($entity_type_id, array $values, $to_resource_uris = FALSE) {
    if (SparqlArg::isValidResources($values)) {
      return $values;
    }

    $bundle_type = $this->entityTypeManager->getStorage($entity_type_id)->getEntityType()->getBundleEntityType();
    $bundle_mappings = $this->getRdfBundleMappedUri($bundle_type);
    foreach ($values as $index => $bundle) {
      if (!isset($bundle_mappings[$bundle])) {
        throw new \Exception("The $bundle bundle does not have a mapping.");
      }
      $values[$index] = $to_resource_uris ? SparqlArg::uri($bundle_mappings[$bundle]) : $bundle_mappings[$bundle];
    }

    return $values;
  }

}
