<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

/**
 * Base class for distribution migration plugins.
 */
abstract class DistributionBase extends JoinupSqlBase {

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
      'nid' => $this->t('Node ID'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('node', 'n');

    $this->alias['asset_distro'] = $query->join('content_field_asset_distribution', 'asset_distro', 'n.nid = %alias.field_asset_distribution_nid');
    $this->alias['node_release'] = $query->join('node', 'node_release', "{$this->alias['asset_distro']}.vid = %alias.vid");
    $this->alias['mapping_table'] = $query->join("{$this->getDestinationDbName()}.joinup_migrate_mapping", 'mapping_table', "{$this->alias['node_release']}.nid = %alias.nid AND %alias.type = 'asset_release' AND %alias.del = 'No'");

    return $query
      ->fields('n', ['nid'])
      ->condition('n.type', 'distribution');
  }

}
