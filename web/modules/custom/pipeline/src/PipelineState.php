<?php

namespace Drupal\pipeline;

/**
 * Represents a state object.
 */
class PipelineState implements PipelineStateInterface {

  /**
   * The step ID.
   *
   * @var string
   */
  protected $stepId;

  /**
   * State data.
   *
   * @var array
   */
  protected $data = [];

  /**
   * {@inheritdoc}
   */
  public function setStepId($step_id) {
    $this->stepId = $step_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStepId() {
    return $this->stepId;
  }

  /**
   * {@inheritdoc}
   */
  public function setData(array $data) {
    $this->data = $data;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDataValue($key, $value) {
    $this->data[$key] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    return $this->data;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataValue($key) {
    $data = $this->getData();
    if (!array_key_exists($key, $data)) {
      throw new \InvalidArgumentException("There's no '$key' key in state data.");
    }
    return $data[$key];
  }

  /**
   * {@inheritdoc}
   */
  public function clearData() {
    $this->data = [];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function unsetDataValue($key) {
    unset($this->data[$key]);
    return $this;
  }

}
