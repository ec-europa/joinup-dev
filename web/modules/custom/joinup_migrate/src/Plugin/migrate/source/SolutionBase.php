<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

/**
 * Base class for solution migrations.
 */
abstract class SolutionBase extends JoinupSqlBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 's',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'nid' => $this->t('ID'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_solution', 's')->fields('s', ['nid']);
  }

}
