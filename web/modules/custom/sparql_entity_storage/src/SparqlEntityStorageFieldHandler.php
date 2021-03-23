<?php

declare(strict_types = 1);

namespace Drupal\sparql_entity_storage;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlArg;
use Drupal\sparql_entity_storage\Event\InboundValueEvent;
use Drupal\sparql_entity_storage\Event\OutboundValueEvent;
use Drupal\sparql_entity_storage\Event\SparqlEntityStorageEvents;
use Drupal\sparql_entity_storage\Exception\NonExistingFieldPropertyException;
use Drupal\sparql_entity_storage\Exception\UnmappedFieldException;
use EasyRdf\Literal;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Contains helper methods that help with the URI mappings of Drupal elements.
 *
 * Mainly, two field maps are created and statically cached:
 *
 * The OUTBOUND MAP, witch is a Drupal oriented property mapping array. A YAML
 * representation of this array would look like:
 * @codingStandardsIgnoreStart
 * sparql_entity:
 *   bundle_key: rid
 *   bundles:
 *     catalog: http://www.w3.org/ns/dcat#Catalog
 *     other_bundle: ...
 *   fields:
 *     label:
 *       type: string
 *       main_property: value
 *       cardinality: 1
 *       predicate: null
 *       columns:
 *         value:
 *           catalog:
 *             predicate: http://purl.org/dc/terms/title
 *             format: t_literal
 *             serialize: false
 *             data_type: string
 *           other_bundle:
 *             predicate: ...
 *             ...
 *         other_column:
 *           catalog:
 *             ...
 *     other_field:
 *       type: entity_reference
 *       main_property: target_id
 *       cardinality: -1
 *       predicate: http://example.com/reference
 *       ...
 * other_entity_type:
 *   bundle_key: ...
 *   ...
 * @codingStandardsIgnoreEnd
 *
 * The INBOUND MAP, witch is a SPARQL oriented property mapping array. A YAML
 * representation of this array would look like:
 * @codingStandardsIgnoreStart
 * sparql_entity:
 *   bundle_key: rid
 *   bundles:
 *     http://www.w3.org/ns/dcat#Catalog:
 *       - catalog
 *       - collection
 *     http://example.com:
 *       - other_bundle
 *   fields:
 *     http://example.com/field/link:
 *       catalog: about_link
 *       collection: link_to
 *     http://example.com/other/field/mapping:
 *       other_bundle: other_field_name
 *       ...
 *     ...
 *   columns:
 *     http://purl.org/dc/terms/title:
 *       catalog:
 *         field_name: label
 *         name: value
 *         serialize: false
 *         data_type: string
 *       other_bundle:
 *         field_name: ...
 *         ...
 *     http://example.com/field_mapping:
 *       ...
 * other_entity_type:
 *   bundle_key: ...
 *   ...
 * @codingStandardsIgnoreEnd
 */
class SparqlEntityStorageFieldHandler implements SparqlEntityStorageFieldHandlerInterface {

  /**
   * The static cache of outbound map.
   *
   * @var array
   */
  protected $outboundMap;

  /**
   * The static cache of inbound map.
   *
   * @var array
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
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * Constructs a QueryFactory object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, EventDispatcherInterface $event_dispatcher, EntityTypeBundleInfoInterface $bundle_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->bundleInfo = $bundle_info;
  }

  /**
   * Prepares the property mappings for the given entity type ID.
   *
   * This is the central point where the field maps SPARQL-to-Drupal (inbound)
   * and Drupal-to-SPARQL (outbound) are build. The parsed results are
   * statically cached.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @throws \Exception
   *   Thrown when a bundle does not have the mapped bundle.
   */
  protected function buildEntityTypeProperties($entity_type_id) {
    if (empty($this->outboundMap[$entity_type_id]) && empty($this->inboundMap[$entity_type_id])) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      $this->outboundMap[$entity_type_id] = $this->inboundMap[$entity_type_id] = [];
      $this->outboundMap[$entity_type_id]['bundle_key'] = $this->inboundMap[$entity_type_id]['bundle_key'] = $entity_type->getKey('bundle');
      foreach ($this->bundleInfo->getBundleInfo($entity_type_id) as $bundle_id => $bundle_info) {
        if (empty($bundle_info['sparql_entity_storage'])) {
          continue;
        }
        $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle_id);
        if (!$bundle_mapping = $bundle_info['sparql_entity_storage']['rdf_type']) {
          throw new \Exception("The {$bundle_info['label']} SPARQL entity does not have an rdf_type set.");
        }
        $this->outboundMap[$entity_type_id]['bundles'][$bundle_id] = $bundle_mapping;
        // More than one Drupal bundle can share the same mapped URI.
        $this->inboundMap[$entity_type_id]['bundles'][$bundle_mapping][] = $bundle_id;
        foreach ($field_definitions as $field_name => $field_definition) {
          $field_storage_definition = $field_definition->getFieldStorageDefinition();

          if ($field_storage_definition instanceof BaseFieldDefinition) {
            $column_mappings = $bundle_info['sparql_entity_storage']['base_fields_mapping'][$field_name] ?? NULL;
            $field_predicate = $bundle_info['sparql_entity_storage']['field_predicates'][$field_name] ?? NULL;
          }
          else {
            $column_mappings = $field_storage_definition->getThirdPartySetting('sparql_entity_storage', 'mapping');
            $field_predicate = $field_storage_definition->getThirdPartySetting('sparql_entity_storage', 'field_predicate');
          }

          // Filter out unmapped columns or columns with invalid predicate.
          $column_mappings = array_filter($column_mappings, function (array $column_mapping): bool {
            return !empty($column_mapping['predicate']) && UrlHelper::isValid($column_mapping['predicate']);
          });

          // Don't process this field if it has no column mappings.
          if (!$column_mappings) {
            continue;
          }

          $this->outboundMap[$entity_type_id]['fields'][$field_name] = [
            'type' => $field_definition->getType(),
            'main_property' => $field_storage_definition->getMainPropertyName(),
            'cardinality' => $field_storage_definition->getCardinality(),
          ];

          if ($is_multi_value = $field_storage_definition->isMultiple()) {
            if (!$field_predicate) {
              @trigger_error('Missing a field-level predicate mapping for multi-value fields is deprecated in sparql_entity_storage:8.x-1.0-alpha9. The field-level predicate mapping for multi-value fields is mandatory in sparql_entity_storage:8.x-1.0-beta1.', E_USER_DEPRECATED);
              $is_multi_value = FALSE;
            }
          }
          $this->outboundMap[$entity_type_id]['fields'][$field_name]['predicate'] = $is_multi_value ? $field_predicate : NULL;
          if ($is_multi_value) {
            $this->inboundMap[$entity_type_id]['fields'][$field_predicate][$bundle_id] = $field_name;
          }
          foreach ($column_mappings as $column_name => $column_mapping) {
            // Handle the serialized values.
            $serialize = !empty($field_storage_definition->getSchema()['columns'][$column_name]['serialize']);

            // Retrieve the property definition primitive data type.
            $property_definition = $field_storage_definition->getPropertyDefinition($column_name);
            if (empty($property_definition)) {
              throw new NonExistingFieldPropertyException("Field '$field_name' of type '{$field_storage_definition->getType()}' has no property '$column_name'.");
            }
            $data_type = $property_definition->getDataType();

            $this->outboundMap[$entity_type_id]['fields'][$field_name]['columns'][$column_name][$bundle_id] = [
              'predicate' => $column_mapping['predicate'],
              'format' => $column_mapping['format'],
              'serialize' => $serialize,
              'data_type' => $data_type,
            ];

            $this->inboundMap[$entity_type_id]['columns'][$column_mapping['predicate']][$bundle_id] = [
              'field_name' => $field_name,
              'name' => $column_name,
              'serialize' => $serialize,
              'data_type' => $data_type,
            ];
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getInboundMap(string $entity_type_id): array {
    $caller_class = debug_backtrace()[1]['class'];
    if (static::class !== $caller_class && !is_subclass_of($caller_class, static::class)) {
      @trigger_error('Calling SparqlEntityStorageFieldHandler::getInboundMap() in public scope is deprecated in sparql_entity_storage:8.x-1.0-alpha9. The method will be protected in sparql_entity_storage:8.x-1.0-beta1. Use interface methods instead.', E_USER_DEPRECATED);
    }

    if (!isset($this->inboundMap[$entity_type_id])) {
      $this->buildEntityTypeProperties($entity_type_id);
    }
    return $this->inboundMap[$entity_type_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldColumnPredicates(string $entity_type_id, string $field_name, ?string $column_name = NULL, ?string $bundle = NULL): array {
    $drupal_to_sparql = $this->getOutboundMap($entity_type_id);
    if (!isset($drupal_to_sparql['fields'][$field_name])) {
      throw new UnmappedFieldException("You are requesting the mapping for a non mapped field: $field_name (entity type: $entity_type_id).");
    }
    $field_mapping = $drupal_to_sparql['fields'][$field_name];
    $column_name = $column_name ?: $field_mapping['main_property'];

    $bundles = $bundle ? [$bundle] : array_keys($drupal_to_sparql['bundles']);
    $return = [];
    foreach ($bundles as $bundle) {
      if (isset($field_mapping['columns'][$column_name][$bundle]['predicate'])) {
        $return[$bundle] = $field_mapping['columns'][$column_name][$bundle]['predicate'];
      }
    }
    return array_filter($return);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldPredicates(string $entity_type_id, string $field_name, ?string $column_name = NULL, ?string $bundle = NULL): array {
    @trigger_error('SparqlEntityStorageFieldHandler::getFieldPredicates() is deprecated in sparql_entity_storage:8.x-1.0-alpha9 and is removed from sparql_entity_storage:8.x-1.0-beta1. Use SparqlEntityStorageFieldHandler::getFieldColumnPredicates() instead', E_USER_DEPRECATED);
    return $this->getFieldColumnPredicates($entity_type_id, $field_name, $column_name, $bundle);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldFormat(string $entity_type_id, string $field_name, ?string $column_name = NULL, ?string $bundle = NULL): array {
    $drupal_to_sparql = $this->getOutboundMap($entity_type_id);
    if (!isset($drupal_to_sparql['fields'][$field_name])) {
      throw new \Exception("You are requesting the mapping for a non mapped field: $field_name.");
    }
    $field_mapping = $drupal_to_sparql['fields'][$field_name];
    $column_name = $column_name ?: $field_mapping['main_property'];

    if (!empty($bundle)) {
      return [$field_mapping['columns'][$column_name][$bundle]['format']];
    }

    return array_values(array_column($field_mapping['columns'][$column_name], 'format'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldMainProperty(string $entity_type_id, string $field_name): string {
    $outbound_data = $this->getOutboundMap($entity_type_id);
    return $outbound_data['fields'][$field_name]['main_property'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyListToArray(string $entity_type_id): array {
    $inbound_map = $this->getInboundMap($entity_type_id);
    return array_unique(array_keys($inbound_map['columns']));
  }

  /**
   * {@inheritdoc}
   */
  public function hasFieldPredicate(string $entity_type_id, string $bundle, string $field_name, string $column_name): bool {
    $drupal_to_sparql = $this->getOutboundMap($entity_type_id);
    return isset($drupal_to_sparql['fields'][$field_name]['columns'][$column_name][$bundle]);
  }

  /**
   * {@inheritdoc}
   */
  public function bundlesToUris(string $entity_type_id, array $bundles, bool $to_resource_uris = FALSE): array {
    if (SparqlArg::isValidResources($bundles)) {
      return $bundles;
    }

    foreach ($bundles as $index => $bundle) {
      $value = $this->getOutboundBundleValue($entity_type_id, $bundle);
      if (empty($value)) {
        throw new \Exception("The $bundle bundle does not have a mapping.");
      }
      $bundles[$index] = $to_resource_uris ? SparqlArg::uri($value) : $value;
    }

    return $bundles;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutboundValue(string $entity_type_id, string $field_name, $value, ?string $langcode = NULL, ?string $column_name = NULL, ?string $bundle = NULL) {
    $outbound_map = $this->getOutboundMap($entity_type_id);
    $format = $this->getFieldFormat($entity_type_id, $field_name, $column_name, $bundle);
    $format = reset($format);

    $field_mapping_info = $this->getFieldInfoFromOutboundMap($entity_type_id, $field_name, $column_name, $bundle);
    $field_mapping_info = reset($field_mapping_info);

    $event = new OutboundValueEvent($entity_type_id, $field_name, $value, $field_mapping_info, $langcode, $column_name, $bundle);
    $this->eventDispatcher->dispatch(SparqlEntityStorageEvents::OUTBOUND_VALUE, $event);
    $value = $event->getValue();

    $serialize = $this->isFieldSerializable($entity_type_id, $field_name, $column_name);
    if ($serialize) {
      $value = serialize($value);
    }

    if ($field_name == $outbound_map['bundle_key']) {
      $value = $this->getOutboundBundleValue($entity_type_id, $value);
    }

    switch ($format) {
      case static::RESOURCE:
        return [
          'type' => substr($value, 0, 2) == '_:' ? 'bnode' : 'uri',
          'value' => $value,
        ];

      case static::NON_TYPE:
        return new Literal($value);

      case static::TRANSLATABLE_LITERAL:
        return Literal::create($value, $langcode);

      default:
        return Literal::create($value, NULL, $format);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getInboundBundleValue(string $entity_type_id, string $bundle_uri): array {
    $inbound_map = $this->getInboundMap($entity_type_id);
    if (empty($inbound_map['bundles'][$bundle_uri])) {
      throw new \Exception("A bundle mapped to <$bundle_uri> was not found.");
    }

    return $inbound_map['bundles'][$bundle_uri];
  }

  /**
   * {@inheritdoc}
   */
  public function getInboundValue(string $entity_type_id, string $field_name, $value, ?string $langcode = NULL, ?string $column_name = NULL, ?string $bundle = NULL) {
    // The outbound map contains the same information as the inbound map: the
    // only difference is how the data is structured. It's safe to retrieve the
    // field information from the outbound map.
    // @see self::buildEntityTypeProperties()
    $field_mapping_info = $this->getFieldInfoFromOutboundMap($entity_type_id, $field_name, $column_name, $bundle);
    $field_mapping_info = reset($field_mapping_info);

    $event = new InboundValueEvent($entity_type_id, $field_name, $value, $field_mapping_info, $langcode, $column_name, $bundle);
    $this->eventDispatcher->dispatch(SparqlEntityStorageEvents::INBOUND_VALUE, $event);
    $value = $event->getValue();

    if ($this->isFieldSerializable($entity_type_id, $field_name, $column_name)) {
      $value = unserialize($value);
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSupportedDataTypes(): array {
    return [
      static::RESOURCE => t('Resource'),
      static::TRANSLATABLE_LITERAL => t('Translatable literal'),
      static::NON_TYPE => t('String (No type)'),
      'xsd:string' => t('Literal'),
      'xsd:boolean' => t('Boolean'),
      'xsd:date' => t('Date'),
      'xsd:dateTime' => t('Datetime'),
      'xsd:decimal' => t('Decimal'),
      'xsd:integer' => t('Integer'),
      'xsd:anyURI' => t('URI (xsd:anyURI)'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldIsMapped(string $entity_type_id, string $field_name): bool {
    $outbound_map = $this->getOutboundMap($entity_type_id);
    return isset($outbound_map['fields'][$field_name]);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldPredicate(string $entity_type_id, string $field_name): ?string {
    return $this->getOutboundMap($entity_type_id)['fields'][$field_name]['predicate'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldCardinality(string $entity_type_id, string $field_name): int {
    return $this->getOutboundMap($entity_type_id)['fields'][$field_name]['cardinality'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAllFieldPredicates(string $entity_type_id): array {
    return array_filter(array_map(function (array $data): ?string {
      return $data['predicate'] ?? NULL;
    }, $this->getOutboundMap($entity_type_id)['fields']));
  }

  /**
   * {@inheritdoc}
   */
  public function getColumnNameByPredicate(string $entity_type_id, string $bundle, string $column_predicate): ?string {
    return $this->getInboundMap($entity_type_id)['columns'][$column_predicate][$bundle]['name'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getColumnFieldNameByPredicate(string $entity_type_id, string $bundle, string $column_predicate): string {
    return $this->getInboundMap($entity_type_id)['columns'][$column_predicate][$bundle]['field_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldType(string $entity_type_id, string $field_name): string {
    return $this->getOutboundMap($entity_type_id)['fields'][$field_name]['type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldNameByPredicate(string $entity_type_id, string $bundle, string $field_predicate): ?string {
    return $this->getInboundMap($entity_type_id)['fields'][$field_predicate][$bundle] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCache(): void {
    unset($this->outboundMap);
    unset($this->inboundMap);
  }

  /**
   * Returns the Drupal-to-SPARQL mapping array.
   *
   * @param string $entity_type_id
   *   The entity type id.
   *
   * @return array
   *   The drupal-to-sparql array.
   */
  protected function getOutboundMap(string $entity_type_id): array {
    if (!isset($this->outboundMap[$entity_type_id])) {
      $this->buildEntityTypeProperties($entity_type_id);
    }
    return $this->outboundMap[$entity_type_id];
  }

  /**
   * Returns whether the field is serializable.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $field_name
   *   The field name.
   * @param string|null $column_name
   *   (optional) The column name. If omitted, the main property will be used.
   *
   * @return bool
   *   Whether the field is serializable.
   *
   * @throws \Exception
   *   Thrown when a non existing field is requested.
   */
  protected function isFieldSerializable(string $entity_type_id, string $field_name, ?string $column_name = NULL): bool {
    $drupal_to_sparql = $this->getOutboundMap($entity_type_id);
    if (!isset($drupal_to_sparql['fields'][$field_name])) {
      throw new \Exception("You are requesting the mapping for a non mapped field: $field_name.");
    }
    $field_mapping = $drupal_to_sparql['fields'][$field_name];
    $column_name = $column_name ?: $field_mapping['main_property'];

    $serialize_array = array_column($field_mapping['columns'][$column_name], 'serialize');
    if (empty($serialize_array)) {
      return FALSE;
    }

    $serialize = reset($serialize_array);
    return $serialize;
  }

  /**
   * Returns the outbound bundle mapping.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle ID.
   *
   * @return string
   *   The bundle mapping.
   *
   * @throws \Exception
   *    Thrown when the bundle is not found.
   */
  protected function getOutboundBundleValue(string $entity_type_id, string $bundle): string {
    $outbound_map = $this->getOutboundMap($entity_type_id);
    if (empty($outbound_map['bundles'][$bundle])) {
      throw new \Exception("The $bundle bundle does not have a mapped id.");
    }

    return $outbound_map['bundles'][$bundle];
  }

  /**
   * Retrieves information about the mapping of a certain field.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $field_name
   *   The field name.
   * @param string|null $column_name
   *   (optional) The column name. If omitted, the field main property is used.
   * @param string|null $bundle
   *   (optional) If passed, filter the final array by bundle.
   *
   * @return array
   *   An associative array with the information about the field mappings.
   *   When no bundle is specified, an array of arrays is returned, where the
   *   first level keys are all the bundles with that field.
   *
   * @throws \Exception
   *   Thrown when the field is not found.
   */
  protected function getFieldInfoFromOutboundMap(string $entity_type_id, string $field_name, ?string $column_name = NULL, ?string $bundle = NULL): array {
    $mapping = $this->getOutboundMap($entity_type_id);

    if (!isset($mapping['fields'][$field_name])) {
      throw new \Exception("You are requesting the mapping info for a non mapped field: $field_name.");
    }

    $field_mapping = $mapping['fields'][$field_name];
    $column_name = $column_name ?: $field_mapping['main_property'];

    if (!empty($bundle)) {
      return [$field_mapping['columns'][$column_name][$bundle]];
    }

    return array_values($field_mapping['columns'][$column_name]);
  }

}
