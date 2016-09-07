<?php

namespace Drupal\asset_distribution;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\og\Og;
use Drupal\rdf_entity\RdfInterface;

/**
 * Includes helper methods to receive associated entities like parent solution.
 *
 * @package Drupal\asset_distribution
 */
class AssetDistributionRelations {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Initialize injected objects.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Returns the solution that a release belongs to.
   *
   * @param \Drupal\rdf_entity\RdfInterface $asset_release
   *    The asset release rdf entity.
   *
   * @return \Drupal\rdf_entity\RdfInterface
   *    The solution rdf entity that the release is version of.
   */
  public function getReleaseSolution(RdfInterface $asset_release) {
    if ($asset_release->bundle() != 'asset_release') {
      return NULL;
    }
    $target_id = $asset_release->field_isr_is_version_of->first()->target_id;
    return $this->entityTypeManager->getStorage('rdf_entity')->load($target_id);
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
    $group_content_ids = Og::getGroupContentIds($solution, ['rdf_entity']);

    if (empty($group_content_ids['rdf_entity'])) {
      return [];
    }

    /** @var array $group_content */
    $group_content = $this->entityTypeManager->getStorage('rdf_entity')
      ->loadMultiple($group_content_ids['rdf_entity']);
    /** @var RdfInterface[] $distributions */
    $distributions = array_filter($group_content, function (RdfInterface $entity) {
      return ($entity->bundle() === 'asset_distribution');
    });

    return $distributions;
  }

}
