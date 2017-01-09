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
      'technique' => $this->t('Representation technique'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();

    return $query
      ->fields($this->alias['node'], ['title', 'vid'])
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
