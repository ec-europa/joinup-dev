<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

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
      'created' => $this->t('Creation date'),
      'body' => $this->t('Description'),
      'changed' => $this->t('Last changed date'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();

    $this->alias['uri'] = $query->leftJoin("{$this->getSourceDbName()}.content_field_id_uri", 'uri', "{$this->alias['node']}.vid = %alias.vid");

    $query->addExpression("{$this->alias['uri']}.field_id_uri_value", 'uri');
    $query->addExpression("FROM_UNIXTIME({$this->alias['node']}.created, '%Y-%m-%dT%H:%i:%s')", 'created');
    $query->addExpression("FROM_UNIXTIME({$this->alias['node']}.changed, '%Y-%m-%dT%H:%i:%s')", 'changed');

    return $query
      ->fields($this->alias['node'], ['title', 'created', 'changed'])
      ->fields($this->alias['node_revision'], ['body']);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {

    // Assure a created date.
    if (!$row->getSourceProperty('created')) {
      $row->setSourceProperty('created', date('Y-m-d\TH:i:s', REQUEST_TIME));
    }
    // Assure a changed date.
    if (!$row->getSourceProperty('changed')) {
      $row->setSourceProperty('changed', date('Y-m-d\TH:i:s', REQUEST_TIME));
    }

    return parent::prepareRow($row);
  }

}
