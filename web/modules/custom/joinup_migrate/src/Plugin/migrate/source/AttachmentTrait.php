<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Attaches files to nodes.
 */
trait AttachmentTrait {

  /**
   * Attaches files to row.
   *
   * @param \Drupal\migrate\Row $row
   *   The source row.
   */
  protected function setAttachment(Row &$row) {
    $fids = $this->select('d8_attachment', 'a')
      ->fields('a', ['nid', 'delta'])
      ->condition('a.nid', $row->getSourceProperty('nid'))
      ->orderBy('a.delta', 'ASC')
      ->execute()
      ->fetchAll();
    $fids = $fids ? array_map(function ($fid) {
      return array_values($fid);
    }, $fids) : NULL;
    $row->setSourceProperty('fids', $fids);
  }

}
