<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Provides a distribution migration source plugin.
 *
 * @MigrateSource(
 *   id = "distribution"
 * )
 */
class Distribution extends DistributionBase {

  use UriTrait;

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'uri' => $this->t('URI'),
      'title' => $this->t('Name'),
      'created_time' => $this->t('Created time'),
      'body' => $this->t('Description'),
      'licence' => $this->t('Licence'),
      'changed_time' => $this->t('Changed time'),
      'technique' => $this->t('Representation technique'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();

    $this->alias['node_revision'] = $query->join('node_revisions', 'node_revision', "{$this->alias['node']}.vid = %alias.vid");

    $query->addExpression("FROM_UNIXTIME({$this->alias['node']}.created, '%Y-%m-%dT%H:%i:%s')", 'created_time');
    $query->addExpression("FROM_UNIXTIME({$this->alias['node']}.changed, '%Y-%m-%dT%H:%i:%s')", 'changed_time');

    $this->alias['content_field_distribution_licence'] = $query->leftJoin('content_field_distribution_licence', 'content_field_distribution_licence', "{$this->alias['node']}.vid = %alias.vid");
    $this->alias['node_licence'] = $query->leftJoin('node', 'node_licence', "{$this->alias['content_field_distribution_licence']}.field_distribution_licence_nid = %alias.nid AND %alias.type = 'licence'");

    $query->addExpression("{$this->alias['node_licence']}.nid", 'licence');

    return $query
      ->fields($this->alias['node'], ['title', 'vid'])
      ->fields($this->alias['node_revision'], ['body'])
      // Assure the URI field.
      ->addTag('uri');
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $nid = $row->getSourceProperty('nid');
    $vid = $row->getSourceProperty('vid');

    // Normalize URI.
    $this->normalizeUri('uri', $row, FALSE);

    // Representation technique.
    $query = $this->select('term_node', 'tn');
    $query->join('term_data', 'td', 'tn.tid = td.tid');
    $representation_technique = $query
      ->fields('td', ['name'])
      ->condition('tn.nid', $nid)
      ->condition('tn.vid', $vid)
      // The representation technique vocabulary vid is 70.
      ->condition('td.vid', 70)
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('technique', $representation_technique);

    return parent::prepareRow($row);
  }

}
