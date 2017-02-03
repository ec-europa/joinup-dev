<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Query\Condition;

/**
 * Base class for solution migrations.
 */
abstract class SolutionBase extends JoinupSqlBase {

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
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->select('joinup_migrate_mapping', 'm');

    $this->alias['collection'] = $query->join('joinup_migrate_collection', 'c', "m.collection = %alias.collection");
    $this->alias['node'] = $query->join('node', 'n', "m.nid = %alias.nid");
    $this->alias['node_revision'] = $query->join('node_revisions', 'node_revision', "{$this->alias['node']}.vid = %alias.vid");
    $this->alias['og_ancestry'] = $query->leftJoin('og_ancestry', 'og_ancestry', "{$this->alias['node']}.nid = %alias.nid");
    $this->alias['node_og'] = $query->leftJoin('node', 'node_og', "{$this->alias['og_ancestry']}.group_nid = %alias.nid AND %alias.type = 'repository'");

    $asset_release_or_project_project = (new Condition('OR'))
      ->condition((new Condition('AND'))
        ->condition('m.type', 'asset_release')
        ->condition("{$this->alias['node_og']}.type", 'repository')
      )
      ->condition('m.type', 'project_project');

    return $query
      ->fields($this->alias['node'], ['nid'])
      ->condition('m.migrate', 1)
      ->condition('m.type', ['asset_release', 'project_project'], 'IN')
      ->condition($asset_release_or_project_project);
  }

}
