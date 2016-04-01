<?php

namespace Drupal\asset_distribution\Controller;


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
    $node = $this->entityTypeManager()->getStorage('rdf_entity')->create(array(
      'rid' => 'asset_distribution',
    ));
    /** @var \Drupal\Core\Form\FormBuilderInterface $form_builder */
    $form = $this->entityFormBuilder()->getForm($node);
    $form['solution'] = [
      '#type' => 'hidden',
      '#value' => $rdf_entity->id(),
    ];
    $form['#submit'][] = 'asset_distribution_form_asset_update_submit';

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
  public function createAssetRepositoryAccess(RdfInterface $rdf_entity) {
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
