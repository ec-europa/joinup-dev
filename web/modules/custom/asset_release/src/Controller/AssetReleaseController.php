<?php

namespace Drupal\asset_release\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\og\OgAccessInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The OG access handler.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * Constructs a AssetReleaseController.
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

  protected $fieldsToCopy = [
    'field_is_description' => 'field_isr_description',
    'field_is_solution_type' => 'field_isr_solution_type',
    'field_is_contact_information' => 'field_isr_contact_information',
    'field_is_owner' => 'field_isr_owner',
    'field_is_related_solutions' => 'field_isr_related_solutions',
    'field_is_included_asset' => 'field_isr_included_asset',
    'field_is_translation' => 'field_isr_translation',
    'field_policy_domain' => 'field_policy_domain',
  ];

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
    // Setup the values for the release.
    $values = [
      'rid' => 'asset_release',
      'field_isr_is_version_of' => $rdf_entity->id(),
    ];

    foreach ($this->fieldsToCopy as $solution_field => $release_field) {
      if (!empty($rdf_entity->get($solution_field)->getValue())) {
        $values[$release_field] = $rdf_entity->get($solution_field)->getValue();
      }
    }

    $asset_release = $this->entityTypeManager()
      ->getStorage('rdf_entity')
      ->create($values);

    $form = $this->entityFormBuilder()->getForm($asset_release);

    return $form;
  }

  /**
   * Handles access to the asset_release add form through solution pages.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The RDF entity for which the custom page is created.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result object.
   */
  public function createAssetReleaseAccess(RdfInterface $rdf_entity) {
    return $this->ogAccess->userAccessEntity('create', $this->createNewAssetRelease($rdf_entity), $this->currentUser());
  }

  /**
   * Creates a new asset_release entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The solution that the asset_release is version of.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The unsaved asset_release entity.
   */
  protected function createNewAssetRelease(RdfInterface $rdf_entity) {
    return $this->entityTypeManager()->getStorage('rdf_entity')->create([
      'rid' => 'asset_release',
      'field_isr_is_version_of' => $rdf_entity->id(),
    ]);
  }

}
