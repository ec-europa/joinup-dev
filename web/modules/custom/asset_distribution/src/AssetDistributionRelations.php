<?php

namespace Drupal\asset_distribution;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service containing methods to get associated entities like parent solution.
 */
class AssetDistributionRelations implements ContainerInjectionInterface {

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
   * Constructs an AssetDistributionRelations service.
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
   * Returns the distributions that are part of a solution.
   *
   * @param \Drupal\rdf_entity\RdfInterface $solution
   *   The solution rdf entity.
   *
   * @return \Drupal\rdf_entity\RdfInterface[]
   *   An array of distributions related to the solution.
   */
  public function getSolutionDistributions(RdfInterface $solution) {
    $group_content_ids = $this->membershipManager->getGroupContentIds($solution, ['rdf_entity']);

    if (empty($group_content_ids['rdf_entity'])) {
      return [];
    }

    /** @var array $group_content */
    $group_content = $this->entityTypeManager->getStorage('rdf_entity')
      ->loadMultiple($group_content_ids['rdf_entity']);
    /** @var \Drupal\rdf_entity\RdfInterface[] $distributions */
    $distributions = array_filter($group_content, function (RdfInterface $entity) {
      return ($entity->bundle() === 'asset_distribution');
    });

    return $distributions;
  }

}
