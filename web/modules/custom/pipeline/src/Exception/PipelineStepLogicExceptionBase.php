<?php

namespace Drupal\pipeline\Exception;

/**
 * Provides a base class for step logical exceptions.
 */
abstract class PipelineStepLogicExceptionBase extends \LogicException {

  /**
   * The step logic exception error render array with message shown to the user.
   *
   * @var array
   */
  protected $error = [];

  /**
   * Sets the step logic exception render array.
   *
   * @param array $error
   *   The step logic exception render array.
   *
   * @return $this
   */
  public function setError(array $error) {
    $this->error = $error;
    return $this;
  }

  /**
   * Returns the step logic exception render array.
   *
   * @return array
   *   The step logic exception render array.
   */
  public function getError() {
    return $this->error;
  }

}
