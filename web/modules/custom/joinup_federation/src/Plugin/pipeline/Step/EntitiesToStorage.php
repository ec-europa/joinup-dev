<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\pipeline\Plugin\PipelineStepWithBatchTrait;
use Drupal\pipeline\Plugin\PipelineStepWithBatchInterface;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Ensures values for empty fields from the staging graph.
 *
 * @PipelineStep(
 *   id = "entities_to_storage",
 *   label = @Translation("Update database records"),
 * )
 */
class EntitiesToStorage extends JoinupFederationStepPluginBase implements PipelineStepWithBatchInterface {

  use PipelineStepWithBatchTrait;
  use SparqlEntityStorageTrait;

  const ENTITIES_TO_STORAGE_KEY = 'entities_to_save';

  /**
   * The batch size.
   *
   * The three way merge is the heaviest process in the import sequence, thus
   * the batch size is 1.
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
  public function initBatchProcess() {
    $entities = $this->getPersistentDataValue(self::ENTITIES_TO_STORAGE_KEY);
    $this->setBatchValue(self::ENTITIES_TO_STORAGE_KEY, $entities);
    $this->unsetPersistentDataValue(self::ENTITIES_TO_STORAGE_KEY);
    return ceil(count($entities) / self::BATCH_SIZE);
  }

  /**
   * {@inheritdoc}
   */
  public function batchProcessIsCompleted() {
    return !$this->getBatchValue(self::ENTITIES_TO_STORAGE_KEY);
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    /** @var \Drupal\Core\Entity\EntityInterface[] $entities */
    $entities = $this->extractNextSubset(self::ENTITIES_TO_STORAGE_KEY, static::BATCH_SIZE);
    foreach ($entities as $entity) {
      $entity->skip_notification = TRUE;
      $entity->save();
    }
  }

}
