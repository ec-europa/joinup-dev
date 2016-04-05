<?php

namespace Drupal\solution\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\rdf_entity\RdfInterface;

/**
 * Class SolutionReleaseController.
 *
 * Hanldes the creation of a new release of a solution.
 *
 * @package Drupal\solution\Controller
 */
class SolutionReleaseController extends ControllerBase {

  /**
   * Controller for the base form.
   *
   * Create a clone of the passed rdf_entity and provide default parameters.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The solution rdf_entity.
   *
   * @return array
   *   Return the form array to be rendered.
   */
  public function add(RdfInterface $rdf_entity) {
    $release = $rdf_entity->createDuplicate();
    $release->set('field_is_is_version_of', $rdf_entity->id());
    $form = $this->entityFormBuilder()->getForm($release, 'release');

    // Override rdf's title.
    $form['#title'] = t('Add release');

    return $form;
  }

  /**
   * Handles access to the page that is responsible to create a release.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The solution for which the release is created.
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
    if ($rdf_entity->bundle() == 'solution' && $this->currentUser()
        ->hasPermission('create solution releases')
    ) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}
