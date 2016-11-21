<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

/**
 * Migrates solutions.
 *
 * @MigrateSource(
 *   id = "solution"
 * )
 */
class Solution extends SolutionBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'uri' => $this->t('URI'),
      'title' => $this->t('Title'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();

    $this->alias['uri'] = $query->leftJoin("{$this->getSourceDbName()}.content_field_id_uri", 'uri', "{$this->alias['node']}.vid = %alias.vid");

    $query->addExpression("{$this->alias['uri']}.field_id_uri_value", 'uri');

    return $query
      ->fields($this->alias['node'], ['title']);
  }

}
