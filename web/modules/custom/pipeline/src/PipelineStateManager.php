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
    $this->getPrivateTempStore()->set($this->getKey($pipeline_id), $step_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getState($pipeline_id) {
    return $this->getPrivateTempStore()->get($this->getKey($pipeline_id));
  }

  /**
   * {@inheritdoc}
   */
  public function reset($pipeline_id) {
    $this->getPrivateTempStore()->delete($this->getKey($pipeline_id));
    return $this;
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
  protected function getKey($pipeline_id) {
    return "state:$pipeline_id";
  }

}
