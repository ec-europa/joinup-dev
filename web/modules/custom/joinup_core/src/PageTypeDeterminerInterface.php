<?php

namespace Drupal\joinup_core;

/**
 * Interface for classes that determine the page type of the current request.
 */
interface PageTypeDeterminerInterface {

  /**
   * Returns the human readable page type.
   *
   * @return string
   *   The human readable page type of the current request.
   */
  public function getType();

}
