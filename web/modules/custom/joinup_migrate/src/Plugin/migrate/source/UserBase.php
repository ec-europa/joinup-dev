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
    $query = $this->select('users', 'u');
    $query->leftJoin('userpoints', 'up', 'u.uid = up.uid');

    return $query
      ->fields('u', ['uid'])
      ->orderBy('u.uid')
      ->condition('u.uid', 0, '>')
      // Only active users.
      ->condition('u.status', 1)
      // Only with kudos >= 10.
      ->condition('up.points', 10, '>=');
  }

}
