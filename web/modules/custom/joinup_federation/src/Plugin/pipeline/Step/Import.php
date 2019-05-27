<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\pipeline\Plugin\PipelineStepWithBatchInterface;
use Drupal\pipeline\Plugin\PipelineStepWithBatchTrait;
use Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_schema_field_validation\SchemaFieldValidatorInterface;
use Drupal\sparql_entity_storage\SparqlGraphInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Merges the incoming data with local data.
 *
 * @PipelineStep(
 *   id = "import",
 *   label = @Translation("Import data into Joinup"),
 * )
 */
class Import extends JoinupFederationStepPluginBase implements PipelineStepWithBatchInterface {

  use PipelineStepWithBatchTrait;
  use SparqlEntityStorageTrait;

  /**
   * The batch size.
   *
   * @var int
   */
  const BATCH_SIZE = 1;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

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
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface $sparql
   *   The SPARQL database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\rdf_schema_field_validation\SchemaFieldValidatorInterface $rdf_schema_field_validator
   *   The RDF schema field validator service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ConnectionInterface $sparql, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, SchemaFieldValidatorInterface $rdf_schema_field_validator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $sparql);
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
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('rdf_schema_field_validation.schema_field_validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function initBatchProcess() {
    // Retrieve the list of entities from the persistent data store as an
    // associative array keyed by entity ID and having a boolean as value,
    // signaling if the entity already exists in Joinup.
    $remaining_entity_ids = $this->getPersistentDataValue('entities');
    $this->setBatchValue('remaining_entity_ids', $remaining_entity_ids);
    return ceil(count($remaining_entity_ids) / static::BATCH_SIZE);
  }

  /**
   * {@inheritdoc}
   */
  public function batchProcessIsCompleted() {
    return !$this->getBatchValue('remaining_entity_ids');
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $ids_to_process = $this->extractNextSubset('remaining_entity_ids', static::BATCH_SIZE);
    $ids = array_keys($ids_to_process);

    // Build a list of local entities that are about to be updated.
    $local_entity_ids = array_keys(array_filter($ids_to_process));
    /** @var \Drupal\rdf_entity\RdfInterface[] $local_entities */
    // @todo Remove the 2nd argument of ::loadMultiple() in ISAICP-4497.
    // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4497
    $local_entities = $local_entity_ids ? Rdf::loadMultiple($local_entity_ids, [SparqlGraphInterface::DEFAULT, 'draft']) : [];

    $entities_to_save = $entities_to_delete = [];
    /** @var \Drupal\rdf_entity\RdfInterface $entity */
    foreach (Rdf::loadMultiple($ids, ['staging']) as $id => $entity) {
      // This entity already exists.
      if ($ids_to_process[$id]) {
        $graph_ids = [];
        foreach ([SparqlGraphInterface::DEFAULT, 'draft'] as $graph_id) {
          if ($local_entities[$id]->hasGraph($graph_id)) {
            $graph_ids[$graph_id] = $graph_id;
          }
        }

        // Pick up first graph to be set.
        $graph_id = key($graph_ids);
        $local_entity = clone $entity;
        $local_entity->set('graph', $graph_id);

        // If the local entity exists in both, 'default' and 'draft', graphs, we
        // remove the 'draft' version. This is needed because the federated
        // fields cannot be edited locally anymore and a potential publish of
        // the draft could override the federated fields values.
        if (count($graph_ids) > 1) {
          $this->getRdfStorage()->deleteFromGraph([$local_entity], 'draft');
        }

        // Collect the entities to be deleted later from the 'staging' graph. We
        // are not deleting here because, when saving the entities in the main
        // graphs, this would lead to a null $entity->original.
        $entities_to_delete[] = $entity;
      }
      // No local entity. Copy the incoming entity as a published entity.
      else {
        $local_entity = (clone $entity)
          ->enforceIsNew()
          ->set('graph', SparqlGraphInterface::DEFAULT);
        // Delete the incoming entity from the staging graph.
        $entity->skip_notification = TRUE;
        $entity->delete();
      }
      $entities_to_save[] = $local_entity;
    }

    // Save the entities.
    foreach ($entities_to_save as $local_entity) {
      $local_entity->skip_notification = TRUE;
      $local_entity->save();
    }

    // Cleanup the entities from the 'staging' graph.
    if ($entities_to_delete) {
      $this->getRdfStorage()->deleteFromGraph($entities_to_delete, 'staging');
    }
  }

}
