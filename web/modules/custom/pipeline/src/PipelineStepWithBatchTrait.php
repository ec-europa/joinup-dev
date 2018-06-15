<?php

namespace Drupal\pipeline;

/**
 * Reusable code for pipeline step plugins with form.
 */
trait PipelineStepWithBatchTrait {
  /**
   * The object keeping track of the progress in the batch operation.
   *
   * @var \Drupal\pipeline\PipelineStepBatchProgressInterface
   */
  protected $progress;

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
