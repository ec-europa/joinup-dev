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
      // @todo Limit distributions to those linked in interoperability solutions
      //   but expand this filter if there's other conclusion in ISAICP-2840,
      //   comment 2003714.
      // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2840?focusedCommentId=2003714&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-2003714
      ->condition("{$this->alias['asset_release_node']}.type", 'asset_release');

    $this->alias['content_field_asset_distribution'] = $query->join('content_field_asset_distribution', 'content_field_asset_distribution', "{$this->alias['asset_release_node']}.vid = %alias.vid");
    $this->alias['node'] = $query->join('node', 'n', "{$this->alias['content_field_asset_distribution']}.field_asset_distribution_nid = %alias.nid");
    $this->alias['mapping'] = $query->join("{$this->getDestinationDbName()}.joinup_migrate_mapping", 'mapping', "{$this->alias['asset_release_node']}.nid = %alias.nid AND %alias.type = 'asset_release' AND %alias.migrate = 1");

    return $query->fields($this->alias['node'], ['nid']);
  }

}
