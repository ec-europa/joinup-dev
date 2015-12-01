<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Controller\TaxonomyController.
 */

namespace Drupal\rdf_entity\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\rdf_entity\RdfEntityTypeInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * Provides route responses for rdf_entity.module.
 */
class RdfController extends ControllerBase {
  /**
   * Route title callback.
   *
   * @param \Drupal\rdf_entity\RdfEntityTypeInterface $rdf_type
   *   The rdf type.
   *
   * @return string
   *   The rdf type label as a render array.
   */
  public function rdfTypeTitle(RdfEntityTypeInterface $rdf_type) {
    return ['#markup' => $rdf_type->label(), '#allowed_tags' => Xss::getHtmlTagList()];
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The rdf entity.
   *
   * @return array
   *   The rdf entity label as a render array.
   */
  public function rdfTitle(RdfInterface $rdf_entity) {
    return ['#markup' => $rdf_entity->getName(), '#allowed_tags' => Xss::getHtmlTagList()];
  }

}
