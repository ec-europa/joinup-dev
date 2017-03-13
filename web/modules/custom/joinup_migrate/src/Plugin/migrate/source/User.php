<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

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
      'roles' => $this->t('Roles'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return parent::query()->fields('u', array_keys($this->fields()));
  }

}
