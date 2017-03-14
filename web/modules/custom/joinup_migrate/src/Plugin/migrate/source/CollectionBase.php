<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

/**
 * Base class for collection migrations.
 */
abstract class CollectionBase extends JoinupSqlBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'collection' => [
        'type' => 'string',
        'alias' => 'c',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'collection' => $this->t('Collection'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_collection', 'c')->fields('c', ['collection']);
  }

}
