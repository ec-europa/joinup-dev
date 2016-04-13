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
   * Controller for the base form.
   *
   * We need to override the functionality of the create form for pages
   * that include the rdf_entity id in the url so that the the solution refers
   * to this asset distribution.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The solution rdf_entity.
   *
   * @return array
   *   Return the form array to be rendered.
   */
  public function add(RdfInterface $rdf_entity) {
    $rdf_entity = $this->entityTypeManager()->getStorage('rdf_entity')->create(array(
      'rid' => 'asset_distribution',
    ));
    /** @var \Drupal\Core\Form\FormBuilderInterface $form_builder */
    $form = $this->entityFormBuilder()->getForm($rdf_entity);
    return $form;
  }

  /**
   * Handles access to the distribution add form through solution pages.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The solution RDF entity for which the distribution is created.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public function createAssetDistributionAccess(RdfInterface $rdf_entity) {
    // Check that the passed in RDF entity is a solution, and that the user
    // has the permission to create distribution entities.
    // @todo Collection owners and facilitators should also have the right to
    //   create distributions related to the solutions they manage.
    // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2450
    if ($rdf_entity->bundle() == 'solution' && $this->currentUser()
        ->hasPermission('create asset_distribution rdf entity')
    ) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}
