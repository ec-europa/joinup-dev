<?php

namespace Drupal\asset_distribution\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\og\Og;
use Drupal\og\OgAccessInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The OG access handler.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * Constructs a CustomPageController.
   *
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access handler.
   */
  public function __construct(OgAccessInterface $og_access) {
    $this->ogAccess = $og_access;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('og.access')
    );
  }

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
    $solution = asset_distribution_get_release_solution($rdf_entity);
    $user = \Drupal::currentUser();
    if (empty($solution) && !$user->isAnonymous()) {
      return AccessResult::neutral();
    }
    $membership = Og::getMembership($user, $solution);
    // @todo: Remove check for empty membership after ISAICP-2369 is in.
    return (!empty($membership) && $membership->hasPermission('create asset_distribution rdf_entity')) ? AccessResult::allowed() : AccessResult::forbidden();
  }

}
