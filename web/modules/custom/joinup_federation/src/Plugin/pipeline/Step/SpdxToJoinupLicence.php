<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\pipeline\Plugin\PipelineStepWithBatchInterface;
use Drupal\pipeline\Plugin\PipelineStepWithBatchTrait;

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
   * {@inheritdoc}
   */
  public function initBatchProcess(): int {
    $conversion_map = $this->getConversionMap();

    $ids = [];
    if (!empty($conversion_map)) {
      $ids = array_values($this->getSparqlQuery()
        ->graphs(['staging'])
        ->condition('rid', 'asset_distribution')
        // Limit to distributions that really have a SPDX licence.
        ->condition('field_ad_licence', array_keys($conversion_map), 'IN')
        ->execute());
    }
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
    /** @var string $id */
    /** @var \Drupal\joinup_licence\Entity\LicenceInterface $licence */
    foreach ($this->getRdfStorage()->loadMultiple($ids) as $id => $licence) {
      $spdx_licence_id = $licence->getSpdxLicenceRdfId();
      if (empty($spdx_licence_id)) {
        continue;
      }
      $conversion_map[$spdx_licence_id] = $id;
      // Cover also an incoming value with '.html' as suffix.
      $conversion_map["{$spdx_licence_id}.html"] = $id;
    }
    $this->setBatchValue('conversion_map', $conversion_map);

    return $conversion_map;
  }

}
