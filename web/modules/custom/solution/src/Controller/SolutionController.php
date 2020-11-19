<?php

declare(strict_types = 1);

namespace Drupal\solution\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\og\Og;
use Drupal\rdf_entity\RdfInterface;

/**
 * Controller for solution forms.
 *
 * Handles the form to perform actions when it is called by a route that
 * includes an rdf_entity id.
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

    // Pass the collection to the form state so that the parent connection is
    // established.
    // @see solution_add_form_parent_submit()
    $form = $this->entityFormBuilder()->getForm($solution, 'default', ['collection' => $rdf_entity->id()]);
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
    // If the collection is archived, content creation is not allowed.
    if ($rdf_entity->bundle() === 'collection' && $rdf_entity->field_ar_state->first()->value === 'archived') {
      return AccessResult::forbidden();
    }

    $user = $this->currentUser();
    if (empty($rdf_entity) && !$user->isAnonymous()) {
      return AccessResult::neutral();
    }

    // Users with 'administer organic groups' permission should have access
    // since this page can only be called from within a group.
    if ($user->hasPermission('administer organic groups')) {
      return AccessResult::allowed();
    }

    $membership = Og::getMembership($rdf_entity, $user);
    return (!empty($membership) && $membership->hasPermission('create solution rdf_entity')) ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * Creates a new solution entity that is affiliated with the given collection.
   *
   * @param \Drupal\rdf_entity\RdfInterface $collection
   *   The collection to affiliate with the new solution.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The unsaved solution entity.
   */
  protected function createNewSolution(RdfInterface $collection) {
    return $this->entityTypeManager()->getStorage('rdf_entity')->create([
      'rid' => 'solution',
      'collection' => [$collection->id()],
    ]);
  }

}
