<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Provides a user migration source plugin.
 *
 * @MigrateSource(
 *   id = "user"
 * )
 */
class User extends UserBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return parent::fields() + $this->baseFields() + [
      'roles' => $this->t('Roles'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return parent::query()->fields('u', array_keys($this->baseFields()));
  }

  /**
   * Provides a list of user base fields.
   *
   * @return array
   *   Associative array keyed by field ID and having the field label as value.
   */
  protected function baseFields() {
    return [
      'status' => $this->t('Status'),
      'name' => $this->t('Username'),
      'pass' => $this->t('Password'),
      'mail' => $this->t('Email address'),
      'created' => $this->t('Registered timestamp'),
      'access' => $this->t('Last access timestamp'),
      'login' => $this->t('Last login timestamp'),
      'timezone' => $this->t('Timezone'),
      'timezone_name' => $this->t('Timezone name'),
      'init' => $this->t('Init'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $roles = $this->select('users_roles', 'ur')
      ->fields('ur', ['rid'])
      ->condition('ur.uid', $row->getSourceProperty('uid'))
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('roles', $roles);

    return parent::prepareRow($row);
  }

}
