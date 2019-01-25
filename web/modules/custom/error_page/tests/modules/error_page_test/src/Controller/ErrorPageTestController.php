<?php

namespace Drupal\error_page_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Testing controller.
 */
class ErrorPageTestController extends ControllerBase {

  /**
   * Simulates an exception.
   */
  public function exception() {
    throw new \Exception('donuts');
  }

  /**
   * Simulates a fatal error.
   */
  public function fatalError() {
    $this->functionDoesNotExist();
  }

  /**
   * Simulates a user error.
   */
  public function userError() {
    trigger_error('donuts', E_USER_ERROR);
    return ['#markup' => 'whatever'];
  }

}
