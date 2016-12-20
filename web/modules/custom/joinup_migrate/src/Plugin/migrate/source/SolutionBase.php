<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

/**
 * Base class for solution migrations.
 */
abstract class SolutionBase extends GroupBase {

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
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();

    $this->alias['node'] = $query->leftJoin("{$this->getSourceDbName()}.node", 'n', "j.nid = %alias.nid");
    $this->alias['node_revision'] = $query->leftJoin("{$this->getSourceDbName()}.node_revisions", 'node_revision', "{$this->alias['node']}.vid = %alias.vid");

    return $query
      ->fields($this->alias['node'], ['nid'])
      ->condition("{$this->alias['node']}.status", 1)
      ->condition("{$this->alias['node']}.type", 'asset_release');
  }

}
