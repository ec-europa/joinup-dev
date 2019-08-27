<?php

declare(strict_types = 1);

namespace Drupal\joinup\Traits;

/**
 * Helper methods for dealing with the search engine.
 */
trait SearchTrait {

  /**
   * Disables the feature to automatically commit the search index on update.
   *
   * This can be used to speed up making changes to a large number of entities
   * in cases where the immediate synchronisation of the search index is not
   * important (for example during a test teardown).
   */
  protected function disableCommitOnUpdate(): void {
    \Drupal::state()->set('joinup_search.skip_solr_commit_on_update', TRUE);
  }

  /**
   * Enables the feature to automatically commit the search index on update.
   */
  protected function enableCommitOnUpdate(): void {
    \Drupal::state()->set('joinup_search.skip_solr_commit_on_update', FALSE);
  }

}
