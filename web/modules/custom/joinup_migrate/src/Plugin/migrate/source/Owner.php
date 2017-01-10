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

  use OwnerTrait;
  use MappingTrait;

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 'n',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'nid' => $this->t('ID'),
      'title' => $this->t('Name'),
      'type' => $this->t('Type'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Build a list of publisher allowed NIDs by querying only the objects that
    // will be migrated (parent collections and solutions).
    $allowed_nids = array_values(array_unique(array_merge(
      $this->getCollectionOwners(),
      $this->getSolutionOwners()
    )));

    $this->alias['node'] = 'n';
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->select('node', $this->alias['node'])
      ->fields($this->alias['node'], ['nid', 'title', 'vid'])
      ->condition("{$this->alias['node']}.status", 1)
      ->condition("{$this->alias['node']}.type", 'publisher');

    if ($allowed_nids) {
      // Limit publishers only to those referred by migrated repositories and
      // interoperability solutions.
      $query->condition("{$this->alias['node']}.nid", $allowed_nids, 'IN');
    }
    else {
      // It there are no allowed NIDs, return nothing.
      $query->condition(1, 2);
    }

    return $query
      // Assure the URI field.
      ->addTag('uri');
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
