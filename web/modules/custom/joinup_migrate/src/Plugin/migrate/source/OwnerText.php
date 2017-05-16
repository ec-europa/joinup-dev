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
      'nid' => [
        'type' => 'integer',
        'alias' => 'm',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'nid' => $this->t('ID'),
      'owner_name' => $this->t('Owner name'),
      'owner_type' => $this->t('Owner type'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_mapping', 'm')
      ->fields('m', ['nid', 'owner_name', 'owner_type'])
      ->isNotNull('m.owner_name');
  }

}
