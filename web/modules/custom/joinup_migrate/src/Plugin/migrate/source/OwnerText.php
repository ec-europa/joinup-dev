<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

/**
 * Migrates text owners.
 *
 * @MigrateSource(
 *   id = "owner_text"
 * )
 */
class OwnerText extends JoinupSqlBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'name' => [
        'type' => 'string',
        'alias' => 'o',
        'max_length' => 2048,
      ],
      'type' => [
        'type' => 'string',
        'alias' => 'o',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'name' => $this->t('Owner name'),
      'type' => $this->t('Owner type'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_owner_text', 'o')->fields('o');
  }

}
