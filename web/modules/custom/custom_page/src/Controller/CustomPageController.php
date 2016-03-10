<?php

/**
 * @file
 * Contains \Drupal\custom_page\Controller\CustomPageController.
 */

namespace Drupal\custom_page\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Class CustomPageController.
 *
 * @package Drupal\custom_page\Controller
 */
class CustomPageController extends ControllerBase {
  /**
   * CustomPageController constructor.
   */
  public function __construct()
  {
  }


  /**
   * Controller for the base form .
   *
   * @param \Drupal\rdf_entity\Rdf $rdf_entity
   *   The collection rdf_entity.
   *
   * @return string
   *   Return Hello string.
   */
  public function add($rdf_entity) {
    $route = \Drupal::routeMatch();

    // @todo: Find why value is not filtered.
    $node = $this->entityTypeManager()->getStorage('node')->create(array(
      'type' => 'custom_page',
    ));

    $form = $this->entityFormBuilder()->getForm($node);

    return $form;
  }

}
