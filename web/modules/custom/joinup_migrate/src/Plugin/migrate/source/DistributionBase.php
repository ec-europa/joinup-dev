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
    $this->alias['asset_release_node'] = 'asset_release_node';

    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->select('node', $this->alias['asset_release_node'])
      ->condition("{$this->alias['asset_release_node']}.type", 'asset_release');

    $this->alias['content_field_asset_distribution'] = $query->join('content_field_asset_distribution', 'content_field_asset_distribution', "{$this->alias['asset_release_node']}.vid = %alias.vid");
    $this->alias['node'] = $query->join('node', 'n', "{$this->alias['content_field_asset_distribution']}.field_asset_distribution_nid = %alias.nid");
    $this->alias['mapping'] = $query->join('d8_mapping', 'mapping', "{$this->alias['asset_release_node']}.nid = %alias.nid AND %alias.type = 'asset_release' AND %alias.migrate = 1");
    $this->alias['og_ancestry'] = $query->join('og_ancestry', 'og_ancestry', "{$this->alias['asset_release_node']}.nid = %alias.nid");
    $this->alias['group_node'] = $query->join('node', 'group_node', "{$this->alias['og_ancestry']}.group_nid = %alias.nid");

    return $query
      ->fields($this->alias['node'], ['nid'])
      ->condition("{$this->alias['group_node']}.type", ['repository', 'project_project'], 'IN');
  }

}
