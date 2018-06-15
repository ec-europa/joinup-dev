<?php

namespace Drupal\pipeline;

use Drupal\Core\TempStore\PrivateTempStoreFactory;

/**
 * Manages the pipeline state.
 */
class PipelineStateManager implements PipelineStateManagerInterface {

  /**
   * The user private temp store factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $privateTempStoreFactory;

  /**
   * The user private temp store.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $privateTempStore;

  /**
   * Constructs a new pipeline state manager service.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $private_tempstore_factory
   *   The user private temp store factory.
   */
  public function __construct(PrivateTempStoreFactory $private_tempstore_factory) {
    $this->privateTempStoreFactory = $private_tempstore_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function setState($pipeline_id, $step_id) {
    $this->getPrivateTempStore()->set($this->getStateKey($pipeline_id), $step_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getState($pipeline_id) {
    return $this->getPrivateTempStore()->get($this->getStateKey($pipeline_id));
  }

  /**
   * {@inheritdoc}
   */
  public function setBatchProgress(string $pipeline_id, PipelineStepBatchProgressInterface $batch_progress) {
    $this->getPrivateTempStore()->set($this->getBatchProgressKey($pipeline_id), $batch_progress);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBatchProgress(string $pipeline_id): PipelineStepBatchProgressInterface {
    $progress = $this->getPrivateTempStore()->get($this->getBatchProgressKey($pipeline_id));
    if (!$progress) {
      $progress = new PipelineStepBatchProgress();
    }
    return $progress;
  }

  /**
   * {@inheritdoc}
   */
  public function reset($pipeline_id) {
    $this->getPrivateTempStore()->delete($this->getStateKey($pipeline_id));
    $this->resetBatchProgress($pipeline_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function resetBatchProgress(string $pipeline_id) {
    $this->getPrivateTempStore()->delete($this->getBatchProgressKey($pipeline_id));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStateMetadata($pipeline_id) {
    return $this->getPrivateTempStore()->getMetadata($this->getStateKey($pipeline_id));
  }

  /**
   * Returns the user private temp store.
   *
   * @return \Drupal\Core\TempStore\PrivateTempStore
   *   The private tempstore.
   */
  protected function getPrivateTempStore() {
    if (!isset($this->privateTempStore)) {
      $this->privateTempStore = $this->privateTempStoreFactory->get('pipeline');
    }
    return $this->privateTempStore;
  }

  /**
   * Builds the private temp store entry key.
   *
   * @param string $pipeline_id
   *   The ID of the pipeline plugin.
   *
   * @return string
   *   The key that identifies a particular pipeline state.
   */
  protected function getStateKey($pipeline_id) {
    return "state:$pipeline_id";
  }

  /**
   * Builds the private temp store entry key.
   *
   * @param string $pipeline_id
   *   The ID of the pipeline plugin.
   *
   * @return string
   *   The key that identifies a particular pipeline sandbox (batch).
   */
  protected function getBatchProgressKey($pipeline_id) {
    return "state:$pipeline_id:sandbox";
  }

}
