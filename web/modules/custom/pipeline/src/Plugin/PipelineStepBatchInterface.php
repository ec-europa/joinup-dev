<?php

namespace Drupal\pipeline\Plugin;

use Drupal\pipeline\PipelineStepBatchProgressInterface;

/**
 * Interface for batch-aware step plugins.
 *
 * @package Drupal\pipeline
 */
interface PipelineStepBatchInterface {

  /**
   * Pass in the progress object to the step.
   *
   * @param \Drupal\pipeline\PipelineStepBatchProgressInterface $progress
   *   Set the batch progress of the step.
   */
  public function setProgress(PipelineStepBatchProgressInterface $progress);

  /**
   * Get the progress object.
   *
   * @return \Drupal\pipeline\PipelineStepBatchProgressInterface
   *   The progress object.
   */
  public function getProgress(): PipelineStepBatchProgressInterface;

}
