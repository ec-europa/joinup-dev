<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;

/**
 * Utility trait to handle information of the federation incoming entities.
 */
trait IncomingEntitiesDataHelperTrait {

    /**
     * A dependency tree for each incoming solution.
     *
     * Each entry is a flat list of entity ids that each solution (the index) is
     * related to.
     *
     * @var array
     */
  protected $solutionData = NULL;

  /**
   * An associative array of hashes indexed by entity id.
   *
   * @var array
   */
  protected $entityHashes = NULL;

  /**
   * Loads the solution data from the persistent state.
   */
  protected function ensureEntityDataLoaded(): void {
    if ($this->solutionData === NULL) {
      $this->solutionData = $this->hasPersistentDataValue('incoming_solution_data') ? $this->getPersistentDataValue('incoming_solution_data') : [];
    }
    if ($this->entityHashes === NULL) {
      $this->entityHashes = $this->hasPersistentDataValue('entity_hashes') ? $this->getPersistentDataValue('entity_hashes') : [];
    }
  }

  /**
   * Stores the entity data to the persistent pipeline state.
   */
  protected function storeEntityData(): void {
    $this->setPersistentDataValue('incoming_solution_data', $this->solutionData);
    $this->setPersistentDataValue('entity_hashes', $this->entityHashes);
  }

  /**
   * Returns whether a solution is on the root of the solution data.
   *
   * @param string $solution_id
   *   The solution entity id.
   *
   * @return bool
   *   Whether the solution id exists on the root level of the solution data.
   */
  protected function solutionDataRootExists(string $solution_id): bool {
    $this->ensureEntityDataLoaded();
    return isset($this->solutionData[$solution_id]);
  }

  /**
   * Adds a solution id on the root of the solution data array.
   *
   * This method does not check if the root already exists and initializes the
   * entry as a new array.
   *
   * @param string $solution_id
   *   The solution entity id.
   */
  protected function addSolutionDataRoot(string $solution_id): void {
    $this->ensureEntityDataLoaded();
    $this->solutionData[$solution_id] = [];
  }

  /**
   * Sets an entity as a dependency to the structured data.
   *
   * @param string $parent
   *   The parent solution entity id.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The child entity.
   */
  protected function addSolutionDataChildDependency(string $parent, EntityInterface $entity): void {
    $this->ensureEntityDataLoaded();
    $this->solutionData[$parent]['dependencies'][$entity->bundle()][$entity->id()] = $entity->id();
  }

  /**
   * Returns whether the given solution has the given entity as a dependency.
   *
   * @param string $parent
   *   The parent solution id.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The candidate child entity.
   *
   * @return bool
   *   Whether the solution already has this entity listed as a dependency.
   */
  protected function hasSolutionDataChildDependency(string $parent, EntityInterface $entity): bool {
    $this->ensureEntityDataLoaded();
    return isset($this->solutionData[$parent]['dependencies'][$entity->bundle()][$entity->id()]);
  }

  /**
   * Returns a list of dependencies for a given solution.
   *
   * @param string $solution_id
   *   The parent solution entity id.
   *
   * @return array
   *   A structured array of entity ids listed as a dependency of the solution
   *   indexed by their bundle.
   */
  protected function getSolutionDataChildDependencies(string $solution_id): array {
    $this->ensureEntityDataLoaded();
    return $this->solutionData[$solution_id]['dependencies'];
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

    $skipped_solutions = array_diff(array_keys($this->solutionData), $solution_ids);
    foreach ($solution_ids as $solution_id) {
      $requested_dependencies = NestedArray::mergeDeepArray([
        $requested_dependencies,
        $this->solutionData[$solution_id]['dependencies'],
      ]);
    }

    $releases = $requested_dependencies['asset_release'] ?: [];
    unset($requested_dependencies['asset_release']);

    $return = $solution_ids + $releases;
    foreach ($requested_dependencies as $ids_per_bundle) {
      $return += $ids_per_bundle;
    }

    // Remove skipped solution ids.
    $return = array_diff($return, $skipped_solutions);
    return $return;
  }

  /**
   * Sets the solution category to the solution data.
   *
   * @param string $solution_id
   *   The solution id.
   * @param string $category
   *   The solution category.
   */
  protected function setSolutionCategory(string $solution_id, string $category): void {
    $this->ensureEntityDataLoaded();
    $this->solutionData[$solution_id]['category'] = $category;
  }

  /**
   * Retrieves the solution category from the persistent state.
   *
   * @param string $solution_id
   *   The solution id.
   *
   * @return string
   *   The solution category.
   *
   * @throws \Exception
   *   Thrown if the category of the solution requested has not been set yet.
   */
  protected function getSolutionCategory(string $solution_id): string {
    $this->ensureEntityDataLoaded();
    if (empty($this->solutionData[$solution_id]['category'])) {
      throw new \Exception("Category has not been set for solution with id {$solution_id}");
    }
    return $this->solutionData[$solution_id]['category'];
  }

  /**
   * Returns whether a solution is listed in the given category
   *
   * @param string $solution_id
   *   The solution entity id.
   * @param string $category
   *   The category to check.
   *
   * @return bool
   *   Whether the solution is listed under this category.
   */
  protected function solutionHasCategory(string $solution_id, string $category): bool {
    $this->ensureEntityDataLoaded();
    return $this->solutionData[$solution_id]['category'] === $category;
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
    return $this->entityHashes[$entity_id] ?? '';
  }

  /**
   * Adds the passed hashes to the list of hashes.
   *
   * @param array $data
   *   An associative array of hashes indexed by the related entity id.
   */
  protected function setEntityHashes(array $data): void {
    $this->ensureEntityDataLoaded();
    $this->entityHashes += $data;
  }

}
