<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

/**
 * Provides a base class for node migrations.
 */
abstract class NodeBase extends JoinupSqlBase {

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
      'vid' => $this->t('Revision ID'),
      'type' => $this->t('Type'),
      'title' => $this->t('Title'),
      'created' => $this->t('Created time'),
      'changed' => $this->t('Changed time'),
      'uid' => $this->t('Author ID'),
      'body' => $this->t('Body'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareQuery() {
    parent::prepareQuery();

    // Add the node common fields.
    $this->query->fields('n', [
      'nid',
      'vid',
      'type',
      'title',
      'created',
      'changed',
      'uid',
      'body',
    ]);

    return $this->query;
  }

}
