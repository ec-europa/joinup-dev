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
  public function add($rdf_entity) {
    $route = \Drupal::routeMatch();

    // @todo: Find why value is not filtered.
    $collection = $route->getParameter('rdf_entity');
    $node = $this->entityManager()->getStorage('node')->create(array(
      'type' => 'custom_page',
    ));

    $form = $this->entityFormBuilder()->getForm($node);

    // Set form defaults.
    $form['og_group_ref']['#access'] = false;
    $form['og_group_ref']['widget'][0]['target_id']['#value']
      = $collection->label() . ' (' . $collection->id() . ')';

    return $form;
  }

}
