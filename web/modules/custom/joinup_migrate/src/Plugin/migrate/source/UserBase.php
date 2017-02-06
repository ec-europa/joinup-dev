<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

/**
 * Base class for user migrations.
 */
abstract class UserBase extends JoinupSqlBase {

  /**
   * Table aliases.
   *
   * @var string[]
   */
  protected $alias = [];

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
    return $this->select('users', 'u')
      ->fields('u', ['uid'])
      ->orderBy('u.uid')
      // Add user migrate filters.
      ->addTag('user_migrate');
  }

}
