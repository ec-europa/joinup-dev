<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

/**
 * Base class for distribution migration plugins.
 */
abstract class DistributionBase extends JoinupSqlBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 'd',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'nid' => $this->t('Node ID'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_distribution', 'd')->fields('d', ['nid']);
  }

}
