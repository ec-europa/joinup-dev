<?php

namespace Drupal\joinup_news\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\rdf_entity\RdfInterface;

/**
 * Controller that handles the form to add news to a collection or a solution.
 *
 * The parent is passed as a parameter from the route.
 *
 * @package Drupal\joinup_news\Controller
 */
class NewsController extends ControllerBase {

  /**
   * Controller for the base form.
   *
   * The main purpose is to automatically reference the parent group entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The collection or solution rdf_entity.
   *
   * @return array
   *   Return the form array to be rendered.
   */
  public function add(RdfInterface $rdf_entity) {
    // Access is only allowed for collections and solutions.
    $field = ($rdf_entity->bundle() == 'collection') ? 'og_group_ref' : 'field_news_parent';
    $node = $this->entityTypeManager()->getStorage('node')->create(array(
      'type' => 'news',
      $field => $rdf_entity->id(),
    ));

    $form = $this->entityFormBuilder()->getForm($node);

    return $form;
  }

  /**
   * Handles access to the news add form through rdf entity pages.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The RDF entity for which the news entity is created.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public function createNewsAccess(RdfInterface $rdf_entity) {
    // Check that the passed in RDF entity is a collection or a solution,
    // and that the user has the permission to create news.
    if (in_array($rdf_entity->bundle(), ['collection', 'solution']) && $this->currentUser()->hasPermission('create rdf entity news')) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}
