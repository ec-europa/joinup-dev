<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Reuse keywords code.
 */
trait KeywordsTrait {

  /**
   * Sets a list of keywords in the row, given $nid and $vid.
   *
   * @param \Drupal\migrate\Row $row
   *   The migrate row.
   * @param string $property
   *   The source peroperty holding the keywords.
   * @param int $nid
   *   The node ID.
   * @param int $vid
   *   The node revision ID.
   */
  public function setKeywords(Row $row, $property, $nid, $vid) {
    $query = $this->select('term_node', 'tn');
    $query->join('term_data', 'td', 'tn.tid = td.tid');
    $keywords = $query
      ->fields('td', ['name'])
      ->condition('tn.nid', $nid)
      ->condition('tn.vid', $vid)
      // The keywords vocabulary vid is 28.
      ->condition('td.vid', 28)
      ->execute()
      ->fetchCol();
    $row->setSourceProperty($property, array_filter(array_unique($keywords)));
  }

}
