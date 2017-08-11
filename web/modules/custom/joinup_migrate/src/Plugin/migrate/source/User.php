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

    // SQL statement to get the destination, given a source path in Drupal 6.
    // @see https://api.drupal.org/api/drupal/includes%21path.inc/function/drupal_lookup_path/6.x
    $sql = "SELECT dst FROM {url_alias} WHERE language IN ('', 'en') AND src = :src ORDER BY pid DESC";

    // Current alias to user page.
    if ($path = $db->queryRange($sql, 0, 1, [':src' => "user/$uid"])->fetchField()) {
      $sources[] = $path;
    }

    // The old profile node ID, from (Drupal 6) Content Profile module.
    $sql_profile = "SELECT nid FROM {node} WHERE type = 'profile' AND uid = :uid";
    if ($nid = $db->query($sql_profile, [':uid' => $uid])->fetchField()) {
      $sources[] = "node/$nid";
      // The profile node use to have its own alias.
      // @see https://api.drupal.org/api/drupal/includes%21path.inc/function/drupal_lookup_path/6.x
      if ($path = $db->queryRange($sql, 0, 1, [':src' => "node/$nid"])->fetchField()) {
        $sources[] = $path;
      }
    }

    return $sources;
  }

}
