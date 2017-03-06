<?php

namespace Drupal\asset_release;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service containing methods to get associated entities like parent solution.
 *
 * @package Drupal\asset_release
 */
class AssetReleaseRelations implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The OG membership manager.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * Constructs an AssetReleaseRelations service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The OG membership manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MembershipManagerInterface $membership_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->membershipManager = $membership_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('og.membership_manager')
    );
  }

  /**
   * Returns the solution that a release belongs to.
   *
   * @param \Drupal\rdf_entity\RdfInterface $asset_release
   *   The asset release rdf entity.
   *
   * @return \Drupal\rdf_entity\RdfInterface
   *   The solution rdf entity that the release is version of.
   */
  public function getReleaseSolution(RdfInterface $asset_release) {
    if ($asset_release->bundle() != 'asset_release') {
      return NULL;
    }
    $target_id = $asset_release->field_isr_is_version_of->first()->target_id;
    return $this->entityTypeManager->getStorage('rdf_entity')->load($target_id);
  }

}
