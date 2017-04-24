<?php

namespace Drupal\rdf_entity;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\rdf_entity\Entity\Query\Sparql\SparqlArg;
use EasyRdf\Literal;

/**
 * Contains helper methods that help with the uri mappings of Drupal elements.
 *
 * @package Drupal\rdf_entity
 */
class RdfFieldHandler {

  const RESOURCE = 'resource';
  const TRANSLATABLE_LITERAL = 't_literal';
  const NON_TYPE = 'literal';

  /**
   * A drupal oriented property mapping array.
   *
   * @var array
   *
   * @todo: More information on the structure?
   */
  protected $outboundMap;

  /**
   * A SPARQL oriented property mapping array.
   *
   * @var array
   *
   * @todo: More information on the structure?
   */
  protected $inboundMap;

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
   *   The entity type id.
   *
   * @throws \Exception
   *    Thrown when a bundle does not have the bundle mapped.
   */
  protected function buildEntityTypeProperties($entity_type_id) {
    if (empty($mapping[$entity_type_id]) && empty($mapping[$entity_type_id])) {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      $bundle_type = $storage->getEntityType()->getBundleEntityType();
      $bundle_storage = $this->entityTypeManager->getStorage($bundle_type);
      $this->outboundMap[$entity_type_id] = $this->inboundMap[$entity_type_id] = [];
      $this->outboundMap[$entity_type_id]['bundle_key'] = $this->inboundMap[$entity_type_id]['bundle_key'] = $bundle_storage->getEntityType()->getKey('id');
      $rdf_bundle_entities = $this->entityTypeManager->getStorage($bundle_type)->loadMultiple();

      $storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
      foreach ($rdf_bundle_entities as $rdf_bundle_entity) {
        $bundle_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $rdf_bundle_entity->id());
        $bundle_definitions_ids = array_keys($bundle_definitions);
        $bundle_mapping = $rdf_bundle_entity->getThirdPartySetting('rdf_entity', 'rdf_type');
        if (empty($bundle_mapping)) {
          throw new \Exception("The {$rdf_bundle_entity->label()} rdf entity does not have an rdf_type set.");
        }
        $this->outboundMap[$entity_type_id]['bundles'][$rdf_bundle_entity->id()] = $bundle_mapping;
        // More than one drupal bundle can share the same mapped uri.
        $this->inboundMap[$entity_type_id]['bundles'][$bundle_mapping][] = $rdf_bundle_entity->id();
        foreach ($storage_definitions as $id => $storage_definition) {
          if (!in_array($id, $bundle_definitions_ids)) {
            continue;
          }

          if ($storage_definition instanceof BaseFieldDefinition) {
            $field_data = rdf_entity_get_third_party_property($rdf_bundle_entity, 'mapping', $id, FALSE);
            $main_property = $storage_definition->getFieldStorageDefinition()->getMainPropertyName();
          }
          else {
            $field_data = $storage_definition->getThirdPartySetting('rdf_entity', 'mapping');
            $main_property = $storage_definition->getMainPropertyName();
          }

          if (!$field_data) {
            continue;
          }

          $this->outboundMap[$entity_type_id]['fields'][$id]['main_property'] = $main_property;
          foreach ($field_data as $column => $column_info) {
            if (empty($column_info['predicate'])) {
              continue;
            }

            // Handle the serialized values.
            $serialize = FALSE;
            $field_storage_schema = $storage_definition->getSchema()['columns'];
            // Inflate value back into a normal item.
            if (isset($field_storage_schema[$column]['serialize']) && $field_storage_schema[$column]['serialize'] === TRUE) {
              $serialize = TRUE;
            }

            $this->outboundMap[$entity_type_id]['fields'][$id]['columns'][$column][$rdf_bundle_entity->id()] = [
              'predicate' => $column_info['predicate'],
              'format' => $column_info['format'],
              'serialize' => $serialize,
            ];

            $this->inboundMap[$entity_type_id]['fields'][$column_info['predicate']][$rdf_bundle_entity->id()] = [
              'field_name' => $id,
              'column' => $column,
              'serialize' => $serialize,
              'type' => $storage_definition->getType(),
            ];
          }
        }
      }
    }
  }

  /**
   * Checks if the drupal-to-sparql array after checking if it needs build.
   *
   * @param string $entity_type_id
   *   The entity type id.
   *
   * @return array
   *   The drupal-to-sparql array.
   */
  public function getOutboundMap($entity_type_id) {
    if (!isset($this->outboundMap[$entity_type_id])) {
      $this->buildEntityTypeProperties($entity_type_id);
    }
    return $this->outboundMap[$entity_type_id];
  }

  /**
   * Checks if the sparql-to-drupal array after checking if it needs build.
   *
   * @param string $entity_type_id
   *   The entity type id.
   *
   * @return array
   *   The sparql-to-drupal array.
   */
  public function getInboundMap($entity_type_id) {
    if (!isset($this->inboundMap[$entity_type_id])) {
      $this->buildEntityTypeProperties($entity_type_id);
    }
    return $this->inboundMap[$entity_type_id];
  }

  /**
   * Returns the predicates for a given field.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $field
   *   The field name.
   * @param string $column
   *   The column name. If empty, the main property will be used instead.
   * @param string $bundle
   *   Optionally filter the final array by bundle.
   *
   * @return array
   *   An array of predicates.
   *
   * @throws \Exception
   *    Thrown when a non existing field is requested.
   */
  public function getFieldPredicates($entity_type_id, $field, $column = NULL, $bundle = NULL) {
    $drupal_to_sparql = $this->getOutboundMap($entity_type_id);
    if (!isset($drupal_to_sparql['fields'][$field])) {
      throw new \Exception("You are requesting the mapping for a non mapped field: $field.");
    }
    $field_mapping = $drupal_to_sparql['fields'][$field];
    $column = $column ?: $field_mapping['main_property'];

    $bundles = $bundle ? [$bundle] : array_keys($drupal_to_sparql['bundles']);
    $return = [];
    foreach ($bundles as $bundle) {
      if (isset($field_mapping['columns'][$column][$bundle]['predicate'])) {
        $return[$bundle] = $field_mapping['columns'][$column][$bundle]['predicate'];
      }
    }
    return array_unique(array_filter($return));
  }

  /**
   * Returns the format for a given field.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $field
   *   The field name.
   * @param string $column
   *   The column name. If empty, the main property will be used instead.
   * @param string $bundle
   *   Optionally filter the final array by bundle.
   *
   * @return array
   *   An array of predicates.
   *
   * @throws \Exception
   *    Thrown when a non existing field is requested.
   */
  public function getFieldFormat($entity_type_id, $field, $column = NULL, $bundle = NULL) {
    $drupal_to_sparql = $this->getOutboundMap($entity_type_id);
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
   * Returns the field's main property.
   *
   * @param string $entity_type_id
   *   The entity type machine name.
   * @param string $field
   *   The field name.
   *
   * @return string
   *   The main property of the field.
   */
  public function getFieldMainProperty($entity_type_id, $field) {
    $outbound_data = $this->getOutboundMap($entity_type_id);
    return $outbound_data['fields'][$field]['main_property'];
  }

  /**
   * Returns a flat list of property Uris of the given entity type id.
   *
   * @param string $entity_type_id
   *   The entity type id.
   *
   * @return array
   *   An array of property Uris that belong to the entity type id.
   */
  public function getPropertyListToArray($entity_type_id) {
    $inbound_map = $this->getInboundMap($entity_type_id);
    $return = array_keys($inbound_map['fields']);
    return array_unique($return);
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
   *   The mapped properties.
   *
   * @deprecated To be replaced by getDrupalToSparql
   */
  public function getEntityPredicates($entity_type_id) {
    return $this->getOutboundMap($entity_type_id);
  }

  /**
   * Returns if the field has a predicate mapped for the given entity type id.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $field
   *   The field name.
   * @param string $column
   *   The column.
   * @param string $bundle
   *   The bundle id.
   *
   * @return bool
   *   Whether the field is mapped for an entity type id.
   */
  public function hasFieldPredicate($entity_type_id, $field, $column, $bundle) {
    $drupal_to_sparql = $this->getOutboundMap($entity_type_id);
    return isset($drupal_to_sparql['fields'][$field]['columns'][$column][$bundle]);
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
    $format = reset($format);
    return $format === self::RESOURCE;
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
  public function fieldIsTranslatableLiteral($entity_type_id, $field_name) {
    $format = $this->getFieldFormat($entity_type_id, $field_name);
    $format = reset($format);
    return $format === self::TRANSLATABLE_LITERAL;
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
   *   The altered array.
   *
   * @throws \Exception
   *    Thrown when the bundle does not have a mapping.
   */
  public function bundlesToUris($entity_type_id, array $values, $to_resource_uris = FALSE) {
    if (SparqlArg::isValidResources($values)) {
      return $values;
    }

    foreach ($values as $index => $bundle) {
      $value = $this->getOutboundBundleValue($entity_type_id, $bundle);
      if (empty($value)) {
        throw new \Exception("The $bundle bundle does not have a mapping.");
      }
      $values[$index] = $to_resource_uris ? SparqlArg::uri($value) : $value;
    }

    return $values;
  }

  /**
   * Returns the outbound value for the given field.
   *
   * This method will be used to convert the value to it's respective SPARQL
   * format e.g. integer value '1' will be converted to '1^^<xsd:integer>'.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $field
   *   The field name.
   * @param string $value
   *   The value to convert.
   * @param string $lang
   *   Optional. Pass the language if one exists. This should be null if the
   *    format is not t_literal.
   * @param string $column
   *   The column for which to calculate the value. If null, the field's main
   *    column will be used.
   *
   * @return string
   *   The calculated value.
   */
  public function getOutboundValue($entity_type_id, $field, $value, $lang = NULL, $column = NULL) {
    $outbound_map = $this->getOutboundMap($entity_type_id);
    $format = $this->getFieldFormat($entity_type_id, $field, $column);
    $format = reset($format);

    if ($field == $outbound_map['bundle_key']) {
      $value = $this->getOutboundBundleValue($entity_type_id, $value);
    }

    switch ($format) {
      case self::RESOURCE:
        return [
          'type' => substr($value, 0, 2) == '_:' ? 'bnode' : 'uri',
          'value' => $value,
        ];

      case self::NON_TYPE:
        return new Literal($value);

      case self::TRANSLATABLE_LITERAL:
        return Literal::create($value, $lang);

      default:
        return Literal::create($value, NULL, $format);
    }
  }

  /**
   * Returns the outbound bundle mapping.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle id.
   *
   * @return string
   *   The bundle mapping.
   *
   * @throws \Exception
   *    Thrown when the bundle is not found.
   */
  public function getOutboundBundleValue($entity_type_id, $bundle) {
    $outbound_map = $this->getOutboundMap($entity_type_id);
    if (empty($outbound_map['bundles'][$bundle])) {
      throw new \Exception("The $bundle bundle does not have a mapped id.");
    }

    return $outbound_map['bundles'][$bundle];
  }

  /**
   * Returns the inbound bundle mapping.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle uri.
   *
   * @return array
   *   An array of bundles that match the requested bundle.
   *
   * @throws \Exception
   *    Thrown when the bundle is not found.
   */
  public function getInboundBundleValue($entity_type_id, $bundle) {
    $outbound_map = $this->getInboundMap($entity_type_id);
    if (empty($outbound_map['bundles'][$bundle])) {
      throw new \Exception("A bundle mapped to <$bundle> was not found.");
    }

    return $outbound_map['bundles'][$bundle];
  }

  /**
   * Returns an array of available datatypes.
   *
   * @return array
   *   An array of datatypes.
   */
  public static function getSupportedDatatypes() {
    return [
      self::RESOURCE => t('Resource'),
      self::TRANSLATABLE_LITERAL => t('Translatable literal'),
      self::NON_TYPE => t('String (No type'),
      'xsd:string' => t('Literal'),
      'xsd:boolean' => t('Boolean'),
      'xsd:date' => t('Date'),
      'xsd:dateTime' => t('Datetime'),
      'xsd:decimal' => t('Decimal'),
      'xsd:integer' => t('Integer'),
    ];
  }

}
