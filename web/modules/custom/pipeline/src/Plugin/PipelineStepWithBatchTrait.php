<?php

namespace Drupal\pipeline\Plugin;

/**
 * Reusable code for pipeline step plugins that are running in batch process.
 *
 * @see \Drupal\pipeline\Plugin\PipelineStepWithBatchInterface
 */
trait PipelineStepWithBatchTrait {

  /**
   * {@inheritdoc}
   */
  public function setBatchValue($key, $value) {
    $this->pipeline->getCurrentState()->setBatchValue($key, $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBatchValue($key) {
    return $this->pipeline->getCurrentState()->getBatchValue($key);
  }

  /**
   * {@inheritdoc}
   */
  public function onBatchProcessCompleted() {
    // Ensure the method with no action for all steps.
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBatchErrorMessages() {
    return $this->pipeline->getCurrentState()->getBatchProcessErrorMessages();
  }

  /**
   * {@inheritdoc}
   */
  public function buildBatchProcessErrorMessage() {
    return $this->getBatchErrorMessages();
  }

  /**
   * Returns the first items from a batch sandbox value and update the value.
   *
   * This is just a code reusing method that allows to extract a set of items
   * from a batch sandbox value, assuming that the value exists and is an array.
   * The method returns the subset and shrinks the batch sandbox value.
   *
   * @param string $key
   *   The batch sandbox value key.
   * @param int $size
   *   The amount of items to extract.
   *
   * @return array
   *   The extracted list of items.
   */
  protected function extractNextSubset($key, $size) {
    $remaining_items = $this->getBatchValue($key);
    $subset = array_splice($remaining_items, 0, $size);
    $this->setBatchValue($key, $remaining_items);
    return $subset;
  }

}
