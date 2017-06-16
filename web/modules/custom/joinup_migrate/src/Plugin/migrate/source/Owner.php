<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\joinup_migrate\RedirectImportInterface;
use Drupal\migrate\Row;

/**
 * Migrates solutions.
 *
 * @MigrateSource(
 *   id = "owner"
 * )
 */
class Owner extends JoinupSqlBase implements RedirectImportInterface {

  use DefaultNodeRedirectTrait;
  use StateTrait;

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
      'owner_type' => $this->t('Owner type'),
      'state' => $this->t('State'),
      'item_state' => $this->t('Item state'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_owner', 'o')
      ->distinct()
      ->fields('o', [
        'type',
        'nid',
        'vid',
        'uri',
        'uid',
        'title',
        'state',
        'item_state',
      ]);
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
    $row->setSourceProperty('owner_type', $type ?: NULL);

    // State.
    $this->setState($row);

    return parent::prepareRow($row);
  }

}
