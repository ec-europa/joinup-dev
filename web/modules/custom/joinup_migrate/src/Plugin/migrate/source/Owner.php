<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\Condition;
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
    // will be migrated (parent repositories or solutions).
    $or = (new Condition('OR'))
      ->isNotNull('r.vid')
      ->isNotNull('s.vid');

    $sub_query = Database::getConnection()->select('joinup_migrate_mapping', 'j')
      ->condition('j.type', ['asset_release', 'repository'], 'IN')
      ->condition('j.del', 'No')
      ->condition('j.collection', ['', '#N/A'], 'NOT IN')
      ->condition('n.status', 1)
      ->condition($or);

    $sub_query->leftJoin(static::getSourceDbName() . '.node', 'n', 'j.nid = n.nid');
    $sub_query->leftJoin(static::getSourceDbName() . '.content_field_repository_publisher', 'r', 'n.vid = r.vid');
    $sub_query->leftJoin(static::getSourceDbName() . '.content_field_asset_publisher', 's', 'n.vid = s.vid');

    // The NID is provided either by repository or by solution.
    $sub_query->addExpression("IFNULL(r.field_repository_publisher_nid, s.field_asset_publisher_nid)", 'allowed_nid');

    $allowed_nids = array_values(array_filter(array_unique($sub_query->execute()->fetchCol())));

    $this->alias['node'] = 'n';
    $query = $this->select('node', $this->alias['node'])
      ->fields($this->alias['node'], ['nid', 'title', 'vid'])
      ->condition("{$this->alias['node']}.status", 1)
      ->condition("{$this->alias['node']}.type", 'publisher')
      // Assure the URI field.
      ->addTag('uri');

    if ($allowed_nids) {
      // Limit publishers only to those refered by migrated repositories and
      // interoperability solutions.
      $query->condition("{$this->alias['node']}.nid", $allowed_nids, 'IN');
    }

    return $query;
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
