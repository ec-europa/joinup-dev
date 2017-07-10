<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Migrates discussions.
 *
 * @MigrateSource(
 *   id = "discussion"
 * )
 */
class Discussion extends NodeBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'status' => $this->t('Status'),
      'collection' => $this->t('Collection'),
      'solution' => $this->t('Solution'),
      'fids' => $this->t('Attachments'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_discussion', 'n')->fields('n', ['status', 'solution']);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $fids = $this->select('content_field_project_issues_attachement', 'a')
      ->fields('a', ['field_project_issues_attachement_fid'])
      ->condition('a.vid', $row->getSourceProperty('vid'))
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('fids', $fids);

    return parent::prepareRow($row);
  }

}
