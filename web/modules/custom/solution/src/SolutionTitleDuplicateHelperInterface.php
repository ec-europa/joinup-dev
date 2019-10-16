<?php

declare(strict_types = 1);

namespace Drupal\solution;

use Drupal\rdf_entity\RdfInterface;

/**
 * Provides an interface the 'solution.title_duplicate_helper' service.
 */
interface SolutionTitleDuplicateHelperInterface {

  /**
   * Checks if the given solution's title is already in use.
   *
   * @param \Drupal\rdf_entity\RdfInterface $solution
   *   The solution to be checked.
   *
   * @return bool
   *   If other solutions with the same doesn't name exist.
   *
   * @throws \Exception
   *   The the passed entity is not a solution.
   */
  public function titleIsUnique(RdfInterface $solution): bool;

  /**
   * Checks if a solution's title is already in use within the same affiliation.
   *
   * @param \Drupal\rdf_entity\RdfInterface $solution
   *   The solution to be checked.
   *
   * @return bool|null
   *   FALSE, if other solutions with the same name exist within the same
   *   affiliation, TRUE otherwise. If the solution's title has duplicates but
   *   its affiliation cannot be determined, returns NULL.
   *
   * @throws \Exception
   *   The the passed entity is not a solution.
   */
  public function titleIsUniqueWithinAffiliation(RdfInterface $solution): ?bool;

}
