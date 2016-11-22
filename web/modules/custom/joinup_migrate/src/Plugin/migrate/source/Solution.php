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
      'keywords' => $this->t('Keywords'),
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
      ->fields($this->alias['node'], ['title', 'created', 'changed', 'vid'])
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

    // Prepare keywords.
    $query = $this->select('term_node', 'tn');
    $query->join('term_data', 'td', 'tn.tid = td.tid');
    $keywords = $query
      ->fields('td', ['name'])
      ->condition('tn.nid', $row->getSourceProperty('nid'))
      ->condition('tn.vid', $row->getSourceProperty('vid'))
      // The keywords vocabulary vid is 28.
      ->condition('td.vid', 28)
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('keywords', $keywords);
drush_print_r( $row->getSourceProperty('nid'));
    drush_print_r($keywords);
    return parent::prepareRow($row);
  }

}
