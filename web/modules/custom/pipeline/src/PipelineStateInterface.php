<?php

namespace Drupal\pipeline;

/**
 * Provides the contract for a pipeline state object.
 */
interface PipelineStateInterface {

  /**
   * Sets the step ID.
   *
   * @param string $step_id
   *   The step ID.
   *
   * @return $this
   */
  public function setStepId($step_id);

  /**
   * Returns the step ID.
   *
   * @return string
   *   The step ID.
   */
  public function getStepId();

  /**
   * Sets the data.
   *
   * @param array $data
   *   An arbitrary associative array with data.
   *
   * @return $this
   */
  public function setData(array $data);

  /**
   * Sets the value for given data entry.
   *
   * @param string $key
   *   The key of entry to be set.
   * @param mixed $value
   *   The value.
   *
   * @return $this
   */
  public function setDataValue($key, $value);

  /**
   * Returns the state data.
   *
   * @return array
   *   The data array.
   */
  public function getData();

  /**
   * Returns a value from the persistent data store given its key.
   *
   * @param string $key
   *   The key of entry to be returned.
   *
   * @return mixed
   *   The value.
   *
   * @throws \InvalidArgumentException
   *   If the data keyed as $key doesn't exist.
   */
  public function getDataValue($key);

  /**
   * Clears the persistent data store.
   *
   * @return $this
   */
  public function clearData();

  /**
   * Un-sets a value from the persistent data store given its key.
   *
   * @param string $key
   *   The key of entry to be unset.
   *
   * @return $this
   */
  public function unsetDataValue($key);

}
