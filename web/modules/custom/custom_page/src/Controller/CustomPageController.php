<?php

namespace Drupal\custom_page\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\og\Og;
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
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public function access() {
    $rdf_entity = \Drupal::routeMatch()->getParameter('rdf_entity');
    $account = \Drupal::currentUser();

    if ($account->isAnonymous()) {
      return AccessResult::forbidden();
    }

    if ($rdf_entity->bundle() != 'collection') {
      return AccessResult::forbidden();
    }

    // @todo: Fix the visibility to include og membership role dependency after ISAICP-2362.
    $user = User::load($account->id());
    if (!(Og::isMember($rdf_entity, $user))) {
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
  }

}
