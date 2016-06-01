<?php

namespace Drupal\joinup_news\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\og\OgAccess;
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
    $node = $this->createNewsEntity($rdf_entity);
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
    if (in_array($rdf_entity->bundle(), ['collection', 'solution'])) {
      if ($this->currentUser()->hasPermission('create rdf entity news')) {
        return AccessResult::allowed();
      }
      if (OgAccess::userAccess($rdf_entity, 'create rdf entity news')->isAllowed()) {
        return AccessResult::allowed();
      }
    }

    return AccessResult::forbidden();
  }

  /**
   * Returns a news content entity.
   *
   * The news content entity is pre-filled with the parent Rdf entity and the
   * initial state.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *    The parent that the news content entity belongs to.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *    A node entity.
   */
  protected function createNewsEntity(RdfInterface $rdf_entity) {
    $field = ($rdf_entity->bundle() == 'collection') ? 'og_group_ref' : 'field_news_parent';
    return $this->entityTypeManager()->getStorage('node')->create([
      'type' => 'news',
      $field => $rdf_entity->id(),
      'field_news_state' => 'draft',
    ]);
  }

}
