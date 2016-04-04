<?php

namespace Drupal\asset_distribution\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\rdf_entity\RdfInterface;

/**
 * Class AssetDistributionController.
 *
 * Handles the form to perform actions when it is called by a route that
 * includes an rdf_entity id.
 *
 * @package Drupal\asset_distribution\Controller
 */
class AssetDistributionController extends ControllerBase {
  /**
   * Handles access to the distribution add form through solution pages.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The solution RDF entity for which the distribution is created.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public function createAssetRepositoryAccess(RdfInterface $rdf_entity) {
    // Check that the passed in RDF entity is a collection, and that the user
    // has the permission to create custom pages.
    // @todo Collection owners and facilitators should also have the right to
    //   create custom pages for the collections they manage.
    // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2450
    if ($rdf_entity->bundle() == 'asset' && $this->currentUser()->hasPermission('create custom collection page')) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}
