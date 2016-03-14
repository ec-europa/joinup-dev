<?php

/**
 * @file
 * Contains \Drupal\custom_page\Controller\CustomPageController.
 */

namespace Drupal\custom_page\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\og\Og;
use Drupal\user\Entity\User;

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
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The collection rdf_entity.
   *
   * @return array
   *   Return the form array to be rendered.
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

  /**
   * Handles accessibility to the custom page add form through collection pages.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public function access(){
    $rdf_entity = \Drupal::routeMatch()->getParameter('rdf_entity');
    $account = \Drupal::currentUser();

    if($account->isAnonymous()){
      return AccessResult::forbidden();
    }

    if($rdf_entity->bundle() != 'collection' ){
      return AccessResult::forbidden();
    }

    // @todo: Fix the visibility to include og membership role dependency after ISAICP-2362.
    $user = User::load($account->id());
    if(!(Og::isMember($rdf_entity, $user))){
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
  }
}
