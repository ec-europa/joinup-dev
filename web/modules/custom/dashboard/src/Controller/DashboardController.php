<?php

namespace Drupal\dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Page controllers for the Dashboard module.
 */
class DashboardController extends ControllerBase {

  /**
   * Renders the main dashboard page.
   */
  public function page() {
    return [
      '#markup' => 'Welcome to your dashboard',
    ];
  }

}
