<?php

declare(strict_types = 1);

namespace Drupal\asset_distribution\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\og\OgAccessInterface;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for asset distribution forms.
 *
 * Handles the form to perform actions when it is called by a route that
 * includes an rdf_entity id.
 */
class AssetDistributionController extends ControllerBase {

  /**
   * The OG access handler.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * {@inheritdoc}
   */
  public function __construct(OgAccessInterface $og_access) {
    $this->ogAccess = $og_access;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('og.access'));
  }

  /**
   * Controller for the base form.
   *
   * We need to override the functionality of the create form for pages
   * that include the rdf_entity id in the url so that the asset release or the
   * solution refers to this asset distribution.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The asset release or solution rdf entity.
   *
   * @return array
   *   Return the form array to be rendered.
   */
  public function add(RdfInterface $rdf_entity) {
    $distribution = $this->createNewAssetDistribution($rdf_entity);
    $form_state_additions = ['redirect' => $rdf_entity->toUrl()];
    return $this->entityFormBuilder()->getForm($distribution, 'default', $form_state_additions);
  }

  /**
   * Handles access to the distribution add form.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The asset release or solution RDF entity for which the distribution
   *   is created.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   */
  public function createAssetDistributionAccess(RdfInterface $rdf_entity): AccessResultInterface {
    // Create a new distribution entity in order to check permissions on it.
    $distribution = $this->createNewAssetDistribution($rdf_entity);

    // If the distribution entity isn't created correctly, forbid access to the
    // page.
    if (!$distribution) {
      return AccessResult::forbidden();
    }

    return $this->ogAccess->userAccessEntityOperation('create', $distribution);
  }

  /**
   * Creates a new asset_distribution entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The RDF entity that the distribution is associated with. Can be either an
   *   'asset_release' or a 'solution'.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The unsaved asset_distribution entity.
   */
  protected function createNewAssetDistribution(RdfInterface $rdf_entity) {
    $solution = $rdf_entity->bundle() === 'solution' ? $rdf_entity : $rdf_entity->field_isr_is_version_of->entity;

    // A solution is needed to create a distribution. If the rdf entity
    // parameter is neither a solution or a release, the variable will be empty.
    if (empty($solution)) {
      return NULL;
    }

    return $this->entityTypeManager()->getStorage('rdf_entity')->create([
      'rid' => 'asset_distribution',
      'parent' => $rdf_entity->id(),
      OgGroupAudienceHelperInterface::DEFAULT_FIELD => $solution->id(),
    ]);
  }

}
