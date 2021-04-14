<?php

declare(strict_types = 1);

namespace Drupal\sparql_entity_storage\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\sparql_entity_storage\SparqlGraphInterface;
use Drupal\sparql_entity_storage\SparqlMappingInterface;
use Drupal\sparql_entity_storage\SparqlEntityStorageInterface;

/**
 * Defines the SPARQL mapping config entity.
 *
 * Used to store mapping between the Drupal bundle settings, including base
 * field definitions, and the RDF backend properties.
 *
 * @ConfigEntityType(
 *   id = "sparql_mapping",
 *   label = @Translation("SPARQL Mapping"),
 *   config_prefix = "mapping",
 *   entity_keys = {
 *     "id" = "id",
 *     "status" = "status"
 *   },
 *   config_export = {
 *     "id",
 *     "entity_type_id",
 *     "bundle",
 *     "rdf_type",
 *     "graph",
 *     "base_fields_mapping",
 *     "entity_id_plugin",
 *   },
 * )
 */
class SparqlMapping extends ConfigEntityBase implements SparqlMappingInterface {

  /**
   * The unique ID of this SPARQL mapping.
   *
   * @var string
   */
  protected $id;

  /**
   * The entity type referred by this mapping.
   *
   * @var string
   */
  protected $entity_type_id;

  /**
   * The bundle referred by this mapping.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The RDF type mapping.
   *
   * @var string
   */
  protected $rdf_type;

  /**
   * The mapping of a graph definition to a graph URI.
   *
   * @var array
   */
  protected $graph = [
    SparqlGraphInterface::DEFAULT => NULL,
  ];

  /**
   * The base fields mapping.
   *
   * @var array
   */
  protected $base_fields_mapping;

  /**
   * The plugin that generates the entity ID.
   *
   * @var string
   */
  protected $entity_id_plugin;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    if (empty($values['entity_type_id'])) {
      throw new \InvalidArgumentException('Missing required property: entity_type_id.');
    }

    // Valid entity type?
    try {
      $storage = $this->entityTypeManager()->getStorage($values['entity_type_id']);
    }
    catch (\Exception $exception) {
      throw new \InvalidArgumentException("Invalid entity type: {$values['entity_type_id']}.");
    }

    // Only entities with SPARQL storage are eligible.
    if (!$storage instanceof SparqlEntityStorageInterface) {
      throw new \InvalidArgumentException("Cannot handle non-SPARQL storage entity type: {$values['entity_type_id']}.");
    }

    // The bundle is the entity type ID, regardless of the passed value.
    if (!$storage->getEntityType()->hasKey('bundle')) {
      $values['bundle'] = $values['entity_type_id'];
    }
    // This entity type supports bundles, then a bundle should have been passed.
    elseif (empty($values['bundle'])) {
      throw new \InvalidArgumentException('Missing required property: bundle.');
    }

    parent::__construct($values, $entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return "{$this->getTargetEntityTypeId()}.{$this->getTargetBundle()}";
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityTypeId(): string {
    return $this->entity_type_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityType(): ?ContentEntityTypeInterface {
    if (!$this->getTargetEntityTypeId()) {
      return NULL;
    }
    return $this->entityTypeManager()->getDefinition($this->getTargetEntityTypeId());
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetBundle(): string {
    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function setRdfType(string $rdf_type): SparqlMappingInterface {
    $this->rdf_type = $rdf_type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRdfType(): ?string {
    return $this->rdf_type;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityIdPlugin(string $entity_id_plugin): SparqlMappingInterface {
    $this->entity_id_plugin = $entity_id_plugin;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityIdPlugin(): ?string {
    return $this->entity_id_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function addGraphs(array $graphs): SparqlMappingInterface {
    $this->graph = $graphs + $this->graph;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setGraphs(array $graphs): SparqlMappingInterface {
    if (!isset($graphs[SparqlGraphInterface::DEFAULT])) {
      throw new \InvalidArgumentException("Passed graphs should include the '" . SparqlGraphInterface::DEFAULT . "' graph.");
    }
    $this->graph = $graphs;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGraphs(): array {
    return $this->graph;
  }

  /**
   * {@inheritdoc}
   */
  public function getGraphUri(string $graph): ?string {
    return $this->graph[$graph] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function unsetGraphs(array $graphs): SparqlMappingInterface {
    $this->graph = array_diff_key($this->graph, array_flip($graphs));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addMappings(array $mappings): SparqlMappingInterface {
    $this->base_fields_mapping = $mappings + $this->base_fields_mapping;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setMappings(array $mappings): SparqlMappingInterface {
    $this->base_fields_mapping = $mappings;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappings(): array {
    return $this->base_fields_mapping;
  }

  /**
   * {@inheritdoc}
   */
  public function getMapping(string $field_name, string $column_name = 'value'): ?array {
    @trigger_error('SparqlMapping::getMapping() is deprecated in sparql_entity_storage:8.x-1.0-alpha9 and is removed in sparql_entity_storage:8.x-1.0-beta1. Use SparqlMapping::getFieldColumnMappingPredicate() and/or SparqlMapping::getFieldColumnMappingFormat() instead', E_USER_DEPRECATED);
    return [
      'predicate' => $this->getFieldColumnMappingPredicate($field_name, $column_name),
      'format' => $this->getFieldColumnMappingFormat($field_name, $column_name),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldMapping(string $field_name): ?array {
    return $this->base_fields_mapping[$field_name]['field'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldColumnMappingPredicate(string $field_name, string $column_name = 'value'): ?string {
    return $this->base_fields_mapping[$field_name][$column_name]['predicate'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldColumnMappingFormat(string $field_name, string $column_name = 'value'): ?string {
    return $this->base_fields_mapping[$field_name][$column_name]['format'] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isMapped(string $field_name, string $column_name = 'value'): bool {
    return $this->getFieldColumnMappingPredicate($field_name, $column_name) && $this->getFieldColumnMappingFormat($field_name, $column_name);
  }

  /**
   * {@inheritdoc}
   */
  public function unsetMappings(array $field_names): SparqlMappingInterface {
    $this->base_fields_mapping = array_diff_key($this->base_fields_mapping, array_flip($field_names));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByName(string $entity_type_id, string $bundle): ?SparqlMappingInterface {
    return static::load("$entity_type_id.$bundle");
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    /** @var \Drupal\sparql_entity_storage\SparqlGraphInterface $graph */
    foreach (SparqlGraph::loadMultiple(array_keys($this->getGraphs())) as $graph) {
      // Add dependency to each graph.
      $this->addDependency($graph->getConfigDependencyKey(), $graph->getConfigDependencyName());
    }

    // Add dependency to the paired bundle entity.
    if ($entity_type = $this->getTargetEntityType()) {
      if ($bundle_entity_type_id = $entity_type->getBundleEntityType()) {
        $bundle_storage = $this->entityTypeManager()->getStorage($bundle_entity_type_id);
        if ($bundle_entity = $bundle_storage->load($this->getTargetBundle())) {
          $this->addDependency($bundle_entity->getConfigDependencyKey(), $bundle_entity->getConfigDependencyName());
        }
      }
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);

    /** @var \Drupal\sparql_entity_storage\SparqlGraphInterface $graph */
    foreach ($dependencies['config'] as $graph) {
      if ($graph->getEntityTypeId() === 'sparql_graph') {
        // Normally we shouldn't be notified about 'default' graph deletion
        // because this could never occur. However, we take this additional
        // precaution to cover any accidental removal.
        if ($graph->id() !== SparqlGraphInterface::DEFAULT) {
          // Remove the reference to the deleted graph and flag this mapping
          // entity to be re-saved.
          $this->unsetGraphs([$graph->id()]);
          $changed = TRUE;
        }
      }
      // Don't react on paired bundle entity deletion (AKA remove this entity).
    }

    return $changed;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    \Drupal::service('sparql.graph_handler')->clearCache();
    \Drupal::service('sparql.field_handler')->clearCache();
    \Drupal::entityTypeManager()->getStorage($this->entity_type_id)->resetCache();
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    \Drupal::service('sparql.graph_handler')->clearCache();
    \Drupal::service('sparql.field_handler')->clearCache();
    /** @var \Drupal\sparql_entity_storage\SparqlMappingInterface $sparql_mapping */
    if ($sparql_mapping = reset($entities)) {
      \Drupal::entityTypeManager()->getStorage($sparql_mapping->getTargetEntityTypeId())->resetCache();
    }
  }

}
