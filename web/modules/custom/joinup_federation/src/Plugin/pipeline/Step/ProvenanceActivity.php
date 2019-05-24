<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\pipeline\Plugin\PipelineStepWithBatchInterface;
use Drupal\pipeline\Plugin\PipelineStepWithBatchTrait;
use Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface;
use Drupal\rdf_entity_provenance\ProvenanceHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a step plugin that updates the provenance activity records.
 *
 * @PipelineStep(
 *   id = "provenance_activity",
 *   label = @Translation("Register the federation activity"),
 * )
 */
class ProvenanceActivity extends JoinupFederationStepPluginBase implements PipelineStepWithBatchInterface {

  use PipelineStepWithBatchTrait;

  /**
   * The batch size.
   *
   * @var int
   */
  const BATCH_SIZE = 100;

  /**
   * The RDF entity provenance helper service.
   *
   * @var \Drupal\rdf_entity_provenance\ProvenanceHelperInterface
   */
  protected $provenanceHelper;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

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
   * @param \Drupal\rdf_entity_provenance\ProvenanceHelperInterface $rdf_entity_provenance_helper
   *   The RDF entity provenance helper service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, ConnectionInterface $sparql, ProvenanceHelperInterface $rdf_entity_provenance_helper, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $sparql);
    $this->provenanceHelper = $rdf_entity_provenance_helper;
    $this->currentUser = $current_user;
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
      $container->get('rdf_entity_provenance.provenance_helper'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function initBatchProcess() {
    $black_list = array_fill_keys($this->getPersistentDataValue('blacklist'), FALSE);
    $entities = array_fill_keys(array_keys($this->getPersistentDataValue('entities')), TRUE);
    $remaining_ids = $black_list + $entities;
    $this->setBatchValue('remaining_ids', $remaining_ids);
    return ceil(count($remaining_ids) / static::BATCH_SIZE);
  }

  /**
   * {@inheritdoc}
   */
  public function batchProcessIsCompleted() {
    return !$this->getBatchValue('remaining_ids');
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $ids = $this->extractNextSubset('remaining_ids', static::BATCH_SIZE);
    $current_user_id = $this->currentUser->id();
    $activities = $this->provenanceHelper->loadOrCreateEntitiesActivity(array_keys($ids));
    $collection_id = $this->getPipeline()->getCollection();
    // Create or update provenance activity records for all entities.
    foreach ($activities as $id => $activity) {
      $activity
        // Set the last user that federated this entity as owner.
        ->setOwnerId($current_user_id)
        ->set('provenance_enabled', $ids[$id])
        ->set('provenance_associated_with', $collection_id)
        ->save();
    }
  }

}
