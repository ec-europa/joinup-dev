<?php

/**
 * @file
 * Contains \Drupal\custom_page\Controller\CustomPageController.
 */

namespace Drupal\custom_page\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class CustomPageController.
 *
 * @package Drupal\custom_page\Controller
 */
class CustomPageController extends ControllerBase {
  /**
   * Controller for the base form .
   *
   * @return string
   *   Return Hello string.
   */
  public function add($rdf_entity) {
    return [
        '#type' => 'markup',
        '#markup' => $this->t("Implement method: add with parameter(s): $rdf_entity")
    ];
  }

}
