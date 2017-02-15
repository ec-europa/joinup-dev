<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

/**
 * Provides a base class for group (collection, solution) source plugins.
 */
abstract class GroupBase extends JoinupSqlBase {

  use MappingTrait;

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'collection' => $this->t('Collection name'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('joinup_migrate_prepare', 'j')
      ->fields('j', ['collection']);
  }

}
