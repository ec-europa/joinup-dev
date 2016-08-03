<?php

namespace Drupal\asset_distribution\Controller;

use Drupal\asset_distribution\AssetDistributionRelations;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelper;
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
   * Drupal\asset_distribution\AssetDistributionRelations definition.
   *
   * @var \Drupal\asset_distribution\AssetDistributionRelations
   */
  protected $assetDistributionRelations;

  /**
   * {@inheritdoc}
   */
  public function __construct(AssetDistributionRelations $asset_distribution_relations) {
    $this->assetDistributionRelations = $asset_distribution_relations;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('asset_distribution.relations')
    );
  }

  /**
   * Controller for the base form.
   *
   * We need to override the functionality of the create form for pages
   * that include the rdf_entity id in the url so that the the asset release
   * refers to this asset distribution.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The asset release rdf entity.
   *
   * @return array
   *   Return the form array to be rendered.
   */
  public function add(RdfInterface $rdf_entity) {
    $distribution = $this->createNewAssetDistribution($rdf_entity);

    /** @var \Drupal\Core\Form\FormBuilderInterface $form_builder */
    $form = $this->entityFormBuilder()->getForm($distribution);

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
    $solution = $this->assetDistributionRelations->getReleaseSolution($rdf_entity);
    $user = $this->currentUser();

    // This form is meant only if a user is adding a distribution through a
    // release of a solution.
    if (empty($solution) && !$user->isAnonymous()) {
      return AccessResult::forbidden();
    }
    $membership = Og::getMembership($user, $solution);
    // @todo: Remove check for empty membership after ISAICP-2369 is in.
    return (!empty($membership) && $membership->hasPermission('create asset_distribution rdf_entity')) ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * Creates a new asset_distribution entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $asset_release
   *   The asset release that the distribution is associated with.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The unsaved asset_distribution entity.
   */
  protected function createNewAssetDistribution(RdfInterface $asset_release) {
    $solution = $this->assetDistributionRelations->getReleaseSolution($asset_release);

    return $this->entityTypeManager()->getStorage('rdf_entity')->create([
      'rid' => 'asset_distribution',
      OgGroupAudienceHelper::DEFAULT_FIELD => $solution->id(),
    ]);
  }

}
