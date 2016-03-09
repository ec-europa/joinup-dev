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
   * CustomPageController constructor.
   */
  public function __construct()
  {
  }


  /**
   * Controller for the base form .
   *
   * @return string
   *   Return Hello string.
   */
  public function add($rdf_entity, $node_type) {
    $route = \Drupal::routeMatch();

    // @todo: Find why value is not filtered.
    $collection = $route->getParameter('rdf_entity');
    $node = $this->entityTypeManager()->getStorage('node')->create(array(
      'type' => $node_type,
    ));

    $form = $this->entityFormBuilder()->getForm($node, 'collection_custom_page');

    return $form;
  }

}
