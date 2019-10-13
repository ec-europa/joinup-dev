<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\Component\Utility\NestedArray;

/**
 * Utility trait to handle information of the federation incoming entities.
 */
trait IncomingEntitiesDataHelperTrait {

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
    $requested_dependencies = [];

    $solution_data = $this->getPersistentDataValue('solution_dependency');
    foreach ($solution_ids as $solution_id) {
      $requested_dependencies = NestedArray::mergeDeepArray([
        $requested_dependencies,
        $solution_data[$solution_id],
      ]);
    }

    // For proper import, releases must be imported right after the solutions
    // so that child entities have the valid reference during Drupal validation.
    $releases = $requested_dependencies['asset_release'] ?? [];
    unset($requested_dependencies['asset_release']);

    $return = $solution_ids + $releases;
    foreach ($requested_dependencies as $ids_per_bundle) {
      $return += $ids_per_bundle;
    }

    return $return;
  }

}
