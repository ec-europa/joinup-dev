<?php

namespace Drupal\asset_distribution;

use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * Returns the release related to a distribution.
   *
   * @param \Drupal\rdf_entity\RdfInterface $asset_distribution
   *   The asset distribution rdf entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The release rdf entity if found, null otherwise.
   */
  public function getDistributionRelease(RdfInterface $asset_distribution) {
    if ($asset_distribution->bundle() != 'asset_distribution') {
      return NULL;
    }

    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query = $this->entityTypeManager->getStorage('rdf_entity')->getQuery();
    $result = $query
      ->condition('field_isr_distribution', $asset_distribution->id())
      ->range(0, 1)
      ->execute();

    if (empty($result)) {
      return NULL;
    }

    $target_id = reset($result);

    return $this->entityTypeManager->getStorage('rdf_entity')->load($target_id);
  }

}
