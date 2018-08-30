<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\pipeline\Plugin\PipelineStepWithBatchTrait;
use Drupal\pipeline\Plugin\PipelineStepWithBatchInterface;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Ensures values for empty fields from the staging graph.
 *
 * @PipelineStep(
 *   id = "assign_affiliation",
 *   label = @Translation("Update affiliations"),
 * )
 */
class AssignAffiliation extends JoinupFederationStepPluginBase implements PipelineStepWithBatchInterface {

  use PipelineStepWithBatchTrait;
  use SparqlEntityStorageTrait;

  /**
   * The batch size.
   *
   * @var int
   */
  const BATCH_SIZE = 1;

  /**
   * Creates a new pipeline step plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\rdf_entity\Database\Driver\sparql\Connection $sparql
   *   The SPARQL database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, Connection $sparql, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $sparql);
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['collection' => NULL] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function initBatchProcess() {
    $incoming_ids = $this->getPersistentDataValue('incoming_ids');
    $this->setBatchValue('remaining_incoming_ids', $incoming_ids);
    return ceil(count($incoming_ids) / self::BATCH_SIZE);
  }

  /**
   * {@inheritdoc}
   */
  public function batchProcessIsCompleted() {
    return !$this->getBatchValue('remaining_incoming_ids');
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $ids_to_process = $this->extractNextSubset('remaining_incoming_ids', static::BATCH_SIZE);
    $local_ids = array_keys(array_filter($ids_to_process));

    // All solutions should already be present in the entities to be saved since
    // all of them have local fields that have values overridden.
    $entities = $this->hasPersistentDataValue(EntitiesToStorage::ENTITIES_TO_STORAGE_KEY) ? $this->getPersistentDataValue(EntitiesToStorage::ENTITIES_TO_STORAGE_KEY) : [];

    /** @var \Drupal\rdf_entity\RdfInterface $incoming_entity */
    foreach ($entities as $id => $incoming_entity) {
      $entity_exists = in_array($id, $local_ids);
      $this->handleAffiliation($incoming_entity, $entity_exists);
      $entities[$id] = $incoming_entity;
    }

    // Persist the list so we can reuse it in the next steps.
    $this->setPersistentDataValue(EntitiesToStorage::ENTITIES_TO_STORAGE_KEY, $entities);
  }

  /**
   * Handles the incoming solution affiliation.
   *
   * For existing solutions, we only check if the configured collection ID
   * matches the solution affiliation. For new solutions, we affiliate the
   * solution to the configured collection.
   *
   * @param \Drupal\rdf_entity\RdfInterface $incoming_solution
   *   The local solution.
   * @param bool $entity_exists
   *   If the incoming entity already exits on the system.
   *
   * @throws \Exception
   *   If the configured collection is different than the collection of the
   *   local solution.
   */
  protected function handleAffiliation(RdfInterface $incoming_solution, bool $entity_exists): void {
    // Check only solutions.
    if ($incoming_solution->bundle() !== 'solution') {
      return;
    }

    // If this plugin was not configured to assign a collection, exit early.
    if (!$collection_id = $this->getConfiguration()['collection']) {
      return;
    }

    if (!$entity_exists) {
      $incoming_solution->set('collection', $collection_id);
      return;
    }

    // Check for collection mismatch when federating an existing solution.
    $match = FALSE;
    foreach ($incoming_solution->get('collection') as $item) {
      if ($item->target_id === $collection_id) {
        $match = TRUE;
        break;
      }
    }

    if (!$match) {
      throw new \Exception("Plugin 'update_local_default_fields' is configured to assign the '$collection_id' collection but the existing solution '{$incoming_solution->id()}' has '{$incoming_solution->collection->target_id}' as collection.");
    }
    // For an existing solution we don't make any changes to its affiliation.
  }

}
