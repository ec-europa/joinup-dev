<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\joinup_migrate\RedirectImportInterface;
use Drupal\migrate\Row;

/**
 * Provides a user migration source plugin.
 *
 * @MigrateSource(
 *   id = "user"
 * )
 */
class User extends UserBase implements RedirectImportInterface {

  use DefaultRedirectTrait;

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

  /**
   * {@inheritdoc}
   */
  public function getRedirectSources(Row $row) {
    $sources = [];
    $uid = $row->getSourceProperty('uid');
    $db = $this->getDatabase();

    $sql = "SELECT nid FROM {node} WHERE type = 'profile' AND uid = :uid";
    if ($nid = $db->query($sql, [':uid' => $uid])->fetchField()) {
      $sources[] = "node/$nid";
      $sql = "SELECT dst FROM {url_alias} WHERE language IN ('', 'en') AND src = :src ORDER BY pid DESC";
      if ($path = $db->queryRange($sql, 0, 1, [':src' => "node/$nid"])->fetchField()) {
        $sources[] = $path;
      }
    }

    $sql = "SELECT dst FROM {url_alias} WHERE language IN ('', 'en') AND src = :src ORDER BY pid DESC";
    if ($path = $db->queryRange($sql, 0, 1, [':src' => "user/$uid"])->fetchField()) {
      $sources[] = $path;
    }

    return $sources;
  }

}
