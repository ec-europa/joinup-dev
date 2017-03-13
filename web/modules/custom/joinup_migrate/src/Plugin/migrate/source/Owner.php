<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Migrates solutions.
 *
 * @MigrateSource(
 *   id = "owner"
 * )
 */
class Owner extends JoinupSqlBase {

  /**
   * {@inheritdoc}
   */
  protected $reservedUriTables = [
    'collection',
    'solution',
    'release',
    'distribution',
  ];

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 'o',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'nid' => $this->t('ID'),
      'uri' => $this->t('URI'),
      'uid' => $this->t('User ID'),
      'title' => $this->t('Name'),
      'type' => $this->t('Type'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_owner', 'o')
      ->distinct()
      ->fields('o', ['nid', 'vid', 'uri', 'uid', 'title']);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Extract publisher type.
    $query = $this->select('term_node', 'tn');
    $query->join('term_data', 'td', 'tn.tid = td.tid');
    $type = $query
      ->fields('td', ['name'])
      ->condition('tn.nid', $row->getSourceProperty('nid'))
      ->condition('tn.vid', $row->getSourceProperty('vid'))
      // The publisher type vocabulary vid is 72.
      ->condition('td.vid', 72)
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('type', $type ?: NULL);

    return parent::prepareRow($row);
  }

}
