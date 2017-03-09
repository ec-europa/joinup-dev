<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

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
      'collection' => $this->t('Collection'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_discussion', 'n');
  }

}
