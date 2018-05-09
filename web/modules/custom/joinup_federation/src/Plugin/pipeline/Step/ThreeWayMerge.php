<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use Drupal\rdf_entity\Entity\Query\Sparql\SparqlQueryInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\Entity\RdfEntityGraph;
use Drupal\rdf_entity\Entity\RdfEntityMapping;
use Drupal\rdf_entity\RdfEntityGraphInterface;
use Drupal\rdf_entity\RdfEntitySparqlStorageInterface;
use Drupal\rdf_entity\RdfInterface;
use Drupal\rdf_schema_field_validation\SchemaFieldValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Merges the incoming data with local data.
 *
 * @PipelineStep(
 *   id = "3_way_merge",
 *   label = @Translation("3-way merge"),
 * )
 */
class ThreeWayMerge extends JoinupFederationStepPluginBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The RDF graph entity.
   *
   * @var \Drupal\rdf_entity\RdfEntityGraphInterface
   */
  protected $graph;

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
   * The RDF entity SPARQL storage.
   *
   * @var \Drupal\rdf_entity\RdfEntitySparqlStorageInterface
   */
  protected $rdfStorage;

  /**
   * The cached SPARQL entity query.
   *
   * @var \Drupal\rdf_entity\Entity\Query\Sparql\SparqlQueryInterface
   */
  protected $sparqlQuery;

  /**
   * The RDF schema field validator service.
   *
   * @var \Drupal\rdf_schema_field_validation\SchemaFieldValidatorInterface
   */
  protected $rdfSchemaFieldValidator;

  /**
   * Creates a new pipeline step plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\rdf_entity\Database\Driver\sparql\Connection $sparql
   *   The SPARQL database connection.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\rdf_schema_field_validation\SchemaFieldValidatorInterface $rdf_schema_field_validator
   *   The RDF schema field validator service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $sparql, AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, SchemaFieldValidatorInterface $rdf_schema_field_validator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $sparql);
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->rdfSchemaFieldValidator = $rdf_schema_field_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('sparql_endpoint'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('rdf_schema_field_validation.schema_field_validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute(array &$data) {
    $sink_graph_id = $this->getSinkGraph()->id();

    // Get the incoming entities.
    $incoming_ids = $this->getSparqlQuery()
      ->graphs([$sink_graph_id])
      ->execute();
    /** @var \Drupal\rdf_entity\RdfInterface[] $incoming_entities */
    $incoming_entities = $incoming_ids ? $this->getRdfStorage()->loadMultiple($incoming_ids, [$sink_graph_id]) : [];

    // Get the incoming entities that are stored also locally.
    $local_ids = $this->getSparqlQuery()
      ->graphs(['default', 'draft'])
      ->condition('id', array_values($incoming_ids), 'IN')
      ->execute();
    /** @var \Drupal\rdf_entity\RdfInterface[] $local_entities */
    $local_entities = $local_ids ? Rdf::loadMultiple($local_ids) : [];

    /** @var \Drupal\rdf_entity\RdfInterface $incoming_entity */
    foreach ($incoming_entities as $id => $incoming_entity) {
      $bundle = $incoming_entity->bundle();

      // The entity already exists.
      if (isset($local_entities[$id])) {
        $local_entity = $local_entities[$id];

        // Check for bundle mismatch between the local and the incoming entity.
        if ($local_entity->bundle() !== $bundle) {
          $arguments = [
            '%id' => $id,
            '%incoming' => $incoming_entity->get('rid')->entity->getSingularLabel(),
            '%local' => $local_entity->get('rid')->entity->getSingularLabel(),
          ];
          return [
            '#markup' => $this->t("The imported @incoming with the ID '%id' tries to override a local %local with the same ID.", $arguments),
          ];
        }

        $needs_save = $this->updateAdmsFields($local_entity, $incoming_entity);
      }
      // No local entity. Copy the incoming entity as a published entity.
      else {
        $local_entity = $incoming_entity;
        $local_entity->setOwnerId($this->currentUser->id());

        // Determine the state field for this bundle, if any.
        $state_field_name = NULL;
        foreach ($this->entityFieldManager->getFieldMapByFieldType('state')['rdf_entity'] as $field_name => $field_info) {
          if (isset($field_info['bundles'][$bundle])) {
            $state_field_name = $field_name;
            break;
          }
        }

        // There are also entities without a state field.
        if ($state_field_name) {
          $local_entity->set($state_field_name, 'validated');
        }
        $local_entity->graph->value = 'default';

        // A new entity needs to be saved.
        $needs_save = TRUE;
      }

      if ($needs_save) {
        $local_entity->save();
      }
    }

    // Cleanup the Drupal representation of the sink graph.
    $this->deleteSinkGraph();
  }

  /**
   * Creates a Drupal representation of the sink graph and return it.
   *
   * @return \Drupal\rdf_entity\RdfEntityGraphInterface
   *   The graph entity.
   */
  protected function getSinkGraph(): RdfEntityGraphInterface {
    if (!isset($this->graph)) {
      $graph_id = "sink_{$this->currentUser->id()}";

      // Try first to get an existing graph.
      if (!$this->graph = RdfEntityGraph::load($graph_id)) {
        // Or create a new one.
        $this->graph = RdfEntityGraph::create([
          'id' => $graph_id,
          'name' => "Sink graph for user #{$this->currentUser->id()}",
          'status' => TRUE,
          'weight' => 1000,
        ]);
        $this->graph->save();
      }

      // Create mappings for each RDF entity bundle.
      foreach ($this->getRdfEntityMappings() as $mapping) {
        if (!$mapping->getGraphUri($graph_id)) {
          $mapping->addGraphs([$graph_id => $this->getGraphUri('sink')])->save();
        }
      }
    }
    return $this->graph;
  }

  /**
   * Deletes the sink graph entity.
   */
  protected function deleteSinkGraph(): void {
    $graph_id = $this->getSinkGraph()->id();
    /** @var \Drupal\rdf_entity\RdfEntityMappingInterface $mapping */
    foreach ($this->getRdfEntityMappings() as $mapping) {
      $mapping->unsetGraphs([$graph_id])->save();
    }
    $this->graph->delete();
  }

  /**
   * Returns the RDF storage.
   *
   * @return \Drupal\rdf_entity\RdfEntitySparqlStorageInterface
   *   The RDF storage.
   */
  protected function getRdfStorage(): RdfEntitySparqlStorageInterface {
    if (!isset($this->rdfStorage)) {
      $this->rdfStorage = $this->entityTypeManager->getStorage('rdf_entity');
    }
    return $this->rdfStorage;
  }

  /**
   * Returns the SPARQL entity query.
   *
   * @return \Drupal\rdf_entity\Entity\Query\Sparql\SparqlQueryInterface
   *   The entity query.
   */
  protected function getSparqlQuery(): SparqlQueryInterface {
    if (!isset($this->sparqlQuery)) {
      $this->sparqlQuery = $this->getRdfStorage()->getQuery();
    }
    return $this->sparqlQuery;
  }

  /**
   * Returns a list of RDF entity mapping entities keyed by entity ID.
   *
   * @return \Drupal\rdf_entity\RdfEntityMappingInterface[]
   *   A list of RDF entity mapping entities keyed by entity ID.
   */
  protected function getRdfEntityMappings(): array {
    $mappings = [];
    /** @var \Drupal\rdf_entity\RdfEntityMappingInterface $mapping */
    foreach (RdfEntityMapping::loadMultiple() as $id => $mapping) {
      if ($mapping->getTargetEntityTypeId() === 'rdf_entity') {
        $mappings[$id] = $mapping;
      }
    }
    return $mappings;
  }

  /**
   * Updates the ADMS field values from the incoming to the local entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $local_entity
   *   The local entity.
   * @param \Drupal\rdf_entity\RdfInterface $incoming_entity
   *   The imported entity.
   *
   * @return bool
   *   If the local entity has been changed and needs to be saved.
   */
  protected function updateAdmsFields(RdfInterface $local_entity, RdfInterface $incoming_entity): bool {
    $changed = FALSE;

    foreach ($local_entity->getFieldDefinitions() as $field_name => $field_definition) {
      // Bypass fields without mapping or fields we don't want to override.
      if (in_array($field_name, ['id', 'rid', 'graph', 'uuid', 'uid'])) {
        continue;
      }
      // Only stored fields are allowed.
      if ($field_definition->isComputed()) {
        continue;
      }

      $columns = $field_definition->getFieldStorageDefinition()->getColumns();
      foreach ($columns as $column_name => $column_schema) {
        // Check if the field is an ADMS-AP field.
        if ($this->rdfSchemaFieldValidator->isDefinedInSchema('rdf_entity', $local_entity->bundle(), $field_name, $column_name)) {
          $incoming_field = $incoming_entity->get($field_name);
          $local_field = $local_entity->get($field_name);
          // Assign only if the incoming and local fields are different.
          if (!$local_field->equals($incoming_field)) {
            $local_field->setValue($incoming_field->getValue());
            $changed = TRUE;
            // Don't check the rest of the columns because the whole field has
            // been already assigned.
            break;
          }
        }
      }
    }

    return $changed;
  }

}
