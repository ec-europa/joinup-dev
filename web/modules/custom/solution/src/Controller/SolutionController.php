<?php

namespace Drupal\solution\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\og\OgAccess;
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
    $solution = $this->entityTypeManager()->getStorage('rdf_entity')->create(array(
      'rid' => 'solution',
      'og_group_ref' => $rdf_entity->id(),
    ));

    $form = $this->entityFormBuilder()->getForm($solution);

    return $form;
  }

  /**
   * Handles access to the solution add form through collection pages.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The RDF entity for which the custom page is created.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public function createSolutionAccess(RdfInterface $rdf_entity) {
    // Check that the passed in RDF entity is a collection, and that the user
    // has the permission to create solutions.
    // @todo Collection owners and facilitators should also have the right to
    //   create solutions for the collections they manage.
    // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2448
    if ($rdf_entity->bundle() == 'collection' && $this->currentUser()->hasPermission('propose solution rdf entity')) {
      return AccessResult::allowed();
    }
    if (OgAccess::userAccess($rdf_entity, 'propose solution rdf entity')->isAllowed()) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}
