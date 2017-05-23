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

  use AttachmentTrait;

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
    $this->setAttachment($row);
    return parent::prepareRow($row);
  }

}
