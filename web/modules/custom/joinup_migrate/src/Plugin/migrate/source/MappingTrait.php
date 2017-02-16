<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

/**
 * Provides base methods for mapping table queries.
 */
trait MappingTrait {

  /**
   * Gets a base query for mapping table.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   Base query for mapping table.
   */
  protected function getMappingBaseQuery() {
    return $this->select('d8_mapping', 'j')
      ->distinct()
      ->condition('j.migrate', 1)
      ->condition('j.collection', ['', '#N/A'], 'NOT IN');
  }

}
