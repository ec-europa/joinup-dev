<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

/**
 * Migrates licences.
 *
 * @MigrateSource(
 *   id = "licence"
 * )
 */
class Licence extends JoinupSqlBase {

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
      'body' => $this->t('Description'),
      'type' => $this->t('Type'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->alias['node'] = 'n';

    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->select('node', $this->alias['node'])
      ->fields($this->alias['node'], ['nid', 'title'])
      ->condition("{$this->alias['node']}.type", 'licence');
    $this->alias['node_revision'] = $query->join('node_revisions', 'node_revision', "{$this->alias['node']}.vid = %alias.vid");
    $this->alias['term_node'] = $query->leftJoin('term_node', 'term_node', "{$this->alias['node']}.vid = %alias.vid");
    // The licence type vocabulary ID is 75.
    $this->alias['term_data'] = $query->leftJoin('term_data', 'term_data', "{$this->alias['term_node']}.tid = %alias.tid AND %alias.vid = 75");

    $query->addField($this->alias['node_revision'], 'body');
    $query->addExpression("{$this->alias['term_data']}.name", 'type');

    return $query
      // Assure the URI field.
      ->addTag('uri');
  }

}
