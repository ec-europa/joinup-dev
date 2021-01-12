<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Event;

use Drupal\pipeline\Plugin\PipelinePipelineInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * A pipeline completion event.
 */
class PipelineCompleteEvent extends Event {

  /**
   * The pipeline that triggered this event.
   *
   * @var \Drupal\pipeline\Plugin\PipelinePipelineInterface
   */
  protected $pipeline;

  /**
   * Whether the pipeline was completed without successfully.
   *
   * @var bool
   */
  protected $success;

  /**
   * Retrieves the pipeline that triggered this event.
   *
   * @return \Drupal\pipeline\Plugin\PipelinePipelineInterface
   *   The pipeline.
   */
  public function getPipeline(): PipelinePipelineInterface {
    return $this->pipeline;
  }

  /**
   * Sets the pipeline that triggered this event.
   *
   * @param \Drupal\pipeline\Plugin\PipelinePipelineInterface $pipeline
   *   The pipeline.
   */
  public function setPipeline(PipelinePipelineInterface $pipeline): void {
    $this->pipeline = $pipeline;
  }

  /**
   * Whether the pipeline was completed successfully.
   *
   * @return bool
   *   The success flag.
   */
  public function isSuccess(): bool {
    return $this->success;
  }

  /**
   * Sets whether the pipeline was executed successfully.
   *
   * @param bool $success
   *   The success flag.
   */
  public function setSuccess(bool $success): void {
    $this->success = $success;
  }

}
