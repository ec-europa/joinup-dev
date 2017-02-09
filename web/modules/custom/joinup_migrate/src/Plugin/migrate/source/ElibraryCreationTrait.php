<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Provides reusing code for Elibrary creation.
 */
trait ElibraryCreationTrait {

  /**
   * Fixes the Elibrary creation field.
   *
   * @param \Drupal\migrate\Row $row
   *   The source row.
   */
  protected function elibraryCreation(Row &$row) {
    $elibrary = $row->getSourceProperty('elibrary');
    if (!in_array($elibrary, [NULL, '0', '1', '2'], TRUE)) {
      $this->migration->getIdMap()->saveMessage($row->getSourceIdValues(), "Elibrary value " . var_export($elibrary, TRUE) . " (allowed 0, 1, 2)");
      $row->setSourceProperty('elibrary', NULL);
    }
  }

}
