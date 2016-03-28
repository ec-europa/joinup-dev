<?php

namespace Drupal\custom_page\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\og\Og;
use Drupal\rdf_entity\RdfInterface;
use Drupal\user\Entity\User;

/**
 * Class CustomPageController.
 *
 * Handles the form to perform actions when it is called by a route that
 * includes an rdf_entity id.
 *
 * @package Drupal\custom_page\Controller
 */
class CustomPageController extends ControllerBase {

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
    $node = $this->entityTypeManager()->getStorage('node')->create(array(
      'type' => 'custom_page',
      'og_group_ref' => $rdf_entity->Id(),
    ));

    $form = $this->entityFormBuilder()->getForm($node);

    return $form;
  }

  /**
   * Handles access to the custom page add form through collection pages.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The RDF entity for which the custom page is created.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public function createCustomPageAccess(RdfInterface $rdf_entity) {
    // Check that the passed in RDF entity is a collection, and that the user
    // has the permission to create custom pages.
    // @todo Collection owners and facilitators should also have the right to
    //   create custom pages for the collections they manage.
    // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2443
    if ($rdf_entity->bundle() == 'collection' && $this->currentUser()->hasPermission('create custom collection page')) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}
