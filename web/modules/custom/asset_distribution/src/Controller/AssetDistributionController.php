<?php

namespace Drupal\asset_distribution\Controller;

use Drupal\asset_distribution\AssetDistributionRelations;
use Drupal\Core\Controller\ControllerBase;
use Drupal\og\OgAccessInterface;
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
   * The OG access handler.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * {@inheritdoc}
   */
  public function __construct(AssetDistributionRelations $asset_distribution_relations, OgAccessInterface $og_access) {
    $this->assetDistributionRelations = $asset_distribution_relations;
    $this->ogAccess = $og_access;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('asset_distribution.relations'),
      $container->get('og.access')
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
   *   The asset release RDF entity for which the distribution is created.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public function createAssetDistributionAccess(RdfInterface $rdf_entity) {
    // Create a new distribution entity in order to check permissions on it.
    $distribution = $this->createNewAssetDistribution($rdf_entity);

    return $this->ogAccess->userAccessEntity('create', $distribution);
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
