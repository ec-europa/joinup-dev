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
    $this->alias['node'] = 'n';
    $query = $this->select('node', $this->alias['node']);

    $this->alias['asset_distro'] = $query->join('content_field_asset_distribution', 'asset_distro', "{$this->alias['node']}.nid = %alias.field_asset_distribution_nid");
    $this->alias['node_release'] = $query->join('node', 'node_release', "{$this->alias['asset_distro']}.vid = %alias.vid");
    $this->alias['mapping'] = $query->join("{$this->getDestinationDbName()}.joinup_migrate_mapping", 'mapping', "{$this->alias['node_release']}.nid = %alias.nid AND %alias.type = 'asset_release' AND %alias.del = 'No'");

    return $query
      ->fields($this->alias['node'], ['nid'])
      ->condition("{$this->alias['node']}.type", 'distribution');
  }

}
