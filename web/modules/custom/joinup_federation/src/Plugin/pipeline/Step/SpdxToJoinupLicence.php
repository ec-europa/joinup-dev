<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\pipeline\Plugin\PipelineStepWithBatchInterface;
use Drupal\pipeline\Plugin\PipelineStepWithBatchTrait;
use Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a process step that converts SPDX into Joinup licences.
 *
 * @PipelineStep(
 *   id = "spdx_to_joinup_licence",
 *   label = @Translation("Convert SPDX into Joinup licences"),
 * )
 */
class SpdxToJoinupLicence extends JoinupFederationStepPluginBase implements PipelineStepWithBatchInterface {

  use PipelineStepWithBatchTrait;
  use SparqlEntityStorageTrait;

  /**
   * The batch size.
   *
   * @var int
   */
  const BATCH_SIZE = 10;

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
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, ConnectionInterface $sparql, EntityTypeManagerInterface $entity_type_manager) {
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
      $container->get('sparql.endpoint'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function initBatchProcess(): int {
    $conversion_map = $this->getConversionMap();

    $ids = array_values($this->getSparqlQuery()
      ->graphs(['staging'])
      ->condition('rid', 'asset_distribution')
      // Limit to distributions that really have a SPDX licence.
      ->condition('field_ad_licence', array_keys($conversion_map), 'IN')
      ->execute());
    $this->setBatchValue('distribution_ids', $ids);

    return (int) ceil(count($ids) / static::BATCH_SIZE);
  }

  /**
   * {@inheritdoc}
   */
  public function batchProcessIsCompleted(): bool {
    return !$this->getBatchValue('distribution_ids');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $conversion_map = $this->getConversionMap();
    $ids_to_process = $this->extractNextSubset('distribution_ids', static::BATCH_SIZE);
    foreach ($this->getRdfStorage()->loadMultiple($ids_to_process, ['staging']) as $distribution) {
      $spdx_licence_id = $distribution->get('field_ad_licence')->target_id;
      $joinup_licence_id = $conversion_map[$spdx_licence_id];
      $distribution->set('field_ad_licence', $joinup_licence_id)->save();
    }
  }

  /**
   * Returns the SPDX > Joinup licence conversion map.
   *
   * @return string[]
   *   An associative array keyed by SPDX licence ID and having the Joinup
   *   licence ID as values.
   */
  protected function getConversionMap(): array {
    if ($this->hasBatchValue('conversion_map')) {
      return $this->getBatchValue('conversion_map');
    }

    // Get all licences with related SPDX licence.
    $ids = $this->getSparqlQuery()
      ->condition('rid', 'licence')
      ->exists('field_licence_spdx_licence')
      ->execute();

    $conversion_map = [];
    foreach ($this->getRdfStorage()->loadMultiple($ids) as $id => $licence) {
      $spdx_licence_id = $licence->get('field_licence_spdx_licence')->target_id;
      $conversion_map[$spdx_licence_id] = $id;
      // Cover also an incoming value with '.html' as suffix.
      $conversion_map["{$spdx_licence_id}.html"] = $id;
    }
    $this->setBatchValue('conversion_map', $conversion_map);

    return $conversion_map;
  }

}
