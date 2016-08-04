<?php

namespace Drupal\solution\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\og\Og;
use Drupal\rdf_entity\RdfInterface;

/**
 * Class SolutionController.
 *
 * Handles the form to perform actions when it is called by a route that
 * includes an rdf_entity id.
 *
 * @package Drupal\solution\Controller
 */
class SolutionController extends ControllerBase {

  /**
   * Controller for the base form.
   *
   * We need to override the functionality of the create form for pages
   * that include the rdf_entity id in the url so that the og audience field
   * is auto completed.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The collection rdf_entity.
   *
   * @return array
   *   Return the form array to be rendered.
   */
  public function add(RdfInterface $rdf_entity) {
    $solution = $this->createNewSolution($rdf_entity);

    $form = $this->entityFormBuilder()->getForm($solution);

    return $form;
  }

  /**
   * Handles access to the solution add form through collection pages.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The RDF entity for which the solution is created.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public function createSolutionAccess(RdfInterface $rdf_entity) {
    $user = $this->currentUser();
    if (empty($rdf_entity) && !$user->isAnonymous()) {
      return AccessResult::neutral();
    }
    $membership = Og::getMembership($rdf_entity, $user);
    return (!empty($membership) && $membership->hasPermission('create solution rdf_entity')) ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * Creates a new solution entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The collection with which the solution will be associated.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The unsaved solution entity.
   */
  protected function createNewSolution(RdfInterface $rdf_entity) {
    return $this->entityTypeManager()->getStorage('rdf_entity')->create([
      'rid' => 'solution',
      'field_is_affiliations_requests' => $rdf_entity->id(),
    ]);
  }

}
