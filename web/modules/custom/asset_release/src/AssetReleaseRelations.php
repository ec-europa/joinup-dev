<?php

namespace Drupal\asset_release;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service containing methods to get associated entities like parent solution.
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
   * The map of 'solution' and 'asset_release' fields that are in sync.
   *
   * @var string[]
   */
  protected static $fieldsInSync = [
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

  /**
   * Synchronizes common fields from the parent solution of a given release.
   *
   * @param \Drupal\rdf_entity\RdfInterface $release
   *   The 'asset_release' RDF entity.
   *
   * @return bool
   *   Whether at least one of the release fields was changed. The caller may
   *   use this value to determine if the entity should be saved or not.
   */
  public function syncFieldsFromParentSolution(RdfInterface $release) {
    $changed = FALSE;

    /** @var \Drupal\rdf_entity\RdfInterface $solution */
    $solution = $release->field_isr_is_version_of->entity;
    if (!$solution || $release->bundle() !== 'asset_release') {
      // Exit here if it's not a release having a valid parent solution.
      return $changed;
    }

    foreach (static::$fieldsInSync as $solution_field => $release_field) {
      if (!$solution->get($solution_field)->equals($release->get($release_field))) {
        $release->set($release_field, $solution->get($solution_field)->getValue());
        $changed = TRUE;
      }
    }

    return $changed;
  }

}
