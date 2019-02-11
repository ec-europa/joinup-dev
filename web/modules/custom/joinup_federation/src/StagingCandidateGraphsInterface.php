<?php

namespace Drupal\joinup_federation;

/**
 * Helper service to provide a list of graph candidates with 'staging' on top.
 */
interface StagingCandidateGraphsInterface {

  /**
   * Provides the list of default graphs but with 'staging' on top.
   *
   * @return string[]
   *   List of candidate graph IDs.
   */
  public function getCandidates(): array;

}
