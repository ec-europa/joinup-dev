<?php

namespace Drupal\asset_release\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\rdf_entity\RdfInterface;

/**
 * Class AssetReleaseController.
 *
 * Handles the form to perform actions when it is called by a route that
 * includes an rdf_entity id.
 *
 * @package Drupal\asset_release\Controller
 */
class AssetReleaseController extends ControllerBase {

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
    $asset_release = $this->entityTypeManager()->getStorage('rdf_entity')->create(array(
      'rid' => 'asset_release',
      'og_group_ref' => $rdf_entity->id(),
    ));

    $form = $this->entityFormBuilder()->getForm($asset_release);

    return $form;
  }

  /**
   * Handles access to the asset_release add form through collection pages.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The RDF entity for which the custom page is created.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public function createAssetReleaseAccess(RdfInterface $rdf_entity) {
    // Check that the passed in RDF entity is a collection, and that the user
    // has the permission to create asset_releases.
    // @todo Collection owners and facilitators should also have the right to
    //   create asset_releases for the collections they manage.
    // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2448
    if ($rdf_entity->bundle() == 'solution' && $this->currentUser()->hasPermission('create asset_release rdf entity')) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}
