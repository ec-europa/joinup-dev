<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\Component\Utility\NestedArray;

/**
 * Utility trait to handle information of the federation incoming entities.
 */
trait IncomingEntitiesDataHelperTrait {

  /**
   * A dependency tree for each incoming solution.
   *
   * An associative array indexed by the solution ids that contains the
   * 'dependencies' and the 'category' values. The 'dependencies' is an
   * associative array itself, of entity ids indexed by their bundle that are
   * related to the solution. The 'category' is a string representing the status
   * of the solution. Possible values are 'not_federated', 'federated',
   * 'invalid_solution', 'federated_unchanged' and 'blacklisted'.
   *
   * @var array
   */
  protected $solutionData;

  /**
   * An associative array of hashes indexed by entity id.
   *
   * @var array
   */
  protected $entityHashes;

  /**
   * Loads the solution data from the persistent state.
   */
  protected function ensureEntityDataLoaded(): void {
    if (!isset($this->solutionData)) {
      $this->solutionData = $this->hasPersistentDataValue('incoming_solution_data') ? $this->getPersistentDataValue('incoming_solution_data') : [];
    }
    if (!isset($this->entityHashes)) {
      $this->entityHashes = $this->hasPersistentDataValue('entity_hashes') ? $this->getPersistentDataValue('entity_hashes') : [];
    }
  }

  /**
   * Returns a flat list of dependencies for a list of solutions.
   *
   * @param array $solution_ids
   *   A list of solution ids.
   *
   * @return array
   *   A flat list of dependencies concerning all solutions in the given list.
   *   The flat list includes the solutions as well and adds the solutions and
   *   the releases first in the list.
   */
  protected function getSolutionsWithDependenciesAsFlatList(array $solution_ids): array {
    $this->ensureEntityDataLoaded();
    $requested_dependencies = [];

    foreach ($solution_ids as $solution_id) {
      $requested_dependencies = NestedArray::mergeDeepArray([
        $requested_dependencies,
        $this->solutionData[$solution_id]['dependencies'],
      ]);
    }

    // For proper import, releases must be imported right after the solutions
    // so that child entities have the valid reference during Drupal validation.
    $releases = $requested_dependencies['asset_release'] ?: [];
    unset($requested_dependencies['asset_release']);

    $return = $solution_ids + $releases;
    foreach ($requested_dependencies as $ids_per_bundle) {
      $return += $ids_per_bundle;
    }

    return $return;
  }

  /**
   * Returns a hash related to a given entity.
   *
   * @param string $entity_id
   *   The entity id.
   *
   * @return string
   *   The hash related to the entity.
   */
  protected function getEntityHash(string $entity_id): string {
    $this->ensureEntityDataLoaded();
    return $this->entityHashes[$entity_id];
  }

}
