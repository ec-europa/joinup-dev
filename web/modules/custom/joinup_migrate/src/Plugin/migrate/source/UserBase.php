<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

/**
 * Base class for user migrations.
 */
abstract class UserBase extends JoinupSqlBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'uid' => [
        'type' => 'integer',
        'alias' => 'u',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'uid' => $this->t('User ID'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_user', 'u')->fields('u', ['uid']);
  }

}
