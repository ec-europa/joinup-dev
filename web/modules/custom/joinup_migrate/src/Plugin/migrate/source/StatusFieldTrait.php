<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Provides helper methods for field status migration.
 */
trait StatusFieldTrait {

  /**
   * Status field mapping.
   *
   * @var string[]
   */
  protected static $statusFieldMap = [
    // Terms from vid 69 ('assert_release', 'distribution').
    11646 => 'http://purl.org/adms/status/Completed',
    11647 => 'http://purl.org/adms/status/Deprecated',
    11648 => 'http://purl.org/adms/status/UnderDevelopment',
    11649 => 'http://purl.org/adms/status/Withdrawn',
    // Terms from vid 43 ('project_project').
    1905 => 'http://purl.org/adms/status/Completed',
    1904 => 'http://purl.org/adms/status/UnderDevelopment',
    1270 => 'http://purl.org/adms/status/UnderDevelopment',
    1269 => 'http://purl.org/adms/status/Withdrawn',
  ];

  /**
   * Sets the status field for a given node revision ID.
   *
   * @param int $vid
   *   Node revision ID.
   * @param \Drupal\migrate\Row $row
   *   The migration row.
   */
  protected function setStatusField($vid, Row &$row) {
    $query = $this->select('term_node', 'tn');
    $query->join('term_data', 'td', 'tn.tid = td.tid');
    $tid = $query
      ->fields('td', ['tid'])
      ->condition('tn.vid', $vid)
      // The status vocabularies vids are 69 and 43.
      ->condition('td.vid', [69, 43], 'IN')
      ->execute()
      ->fetchField();

    $status_id = isset(static::$statusFieldMap[$tid]) ? static::$statusFieldMap[$tid] : NULL;

    $row->setSourceProperty('status_field', $status_id);
  }

}
