<?php

/**
 * @file
 * Contains \Drupal\custom_page\Controller\CustomPageController.
 */

namespace Drupal\custom_page\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\rdf_entity\Entity\Rdf;

// @todo: Fix the description.
/**
 * Class CustomPageController.
 *
 * @package Drupal\custom_page\Controller
 */
class CustomPageController extends ControllerBase {
  // @todo: Fix the description.
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
    // @todo: Find why value is not filtered.
    $node = $this->entityTypeManager()->getStorage('node')->create(array(
      'type' => 'custom_page',
      'og_group_ref' => $rdf_entity->Id()
    ));

    // @todo: Change form name to include '_form' suffix.
    $form = $this->entityFormBuilder()->getForm($node, 'collection_custom_page');

    return $form;
  }

  // @todo: Fix access.
}
