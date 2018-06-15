<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\pipeline\PipelineStepBatchProgressInterface;
use Drupal\pipeline\Plugin\PipelineStepBatchInterface;

/**
 * Defines a manual data upload step plugin.
 *
 * @PipelineStep(
 *   id = "batch_test",
 *   label = @Translation("Batch test"),
 * )
 */
class BatchTest extends JoinupFederationStepPluginBase implements PipelineStepBatchInterface {

  /**
   * The object keeping track of the progress in the batch operation.
   *
   * @var \Drupal\pipeline\PipelineStepBatchProgressInterface
   */
  protected $progress;

  /**
   * {@inheritdoc}
   */
  public function execute(array &$data) {

    if ($this->progress->needsInitialisation()) {
      $this->progress->setTotalBatchIterations(5);
    }
    sleep(1);
    $this->progress->setBatchIteration($this->progress->getBatchIteration() + 1);
    if ($this->progress->getBatchIteration() >= $this->progress->getTotalBatchIterations()) {
      $this->progress->setCompleted();
    }

  }

  /**
   * {@inheritdoc}
   */
  public function setProgress(PipelineStepBatchProgressInterface $progress) {
    $this->progress = $progress;
  }

  /**
   * {@inheritdoc}
   */
  public function getProgress(): PipelineStepBatchProgressInterface {
    return $this->progress;
  }

}
