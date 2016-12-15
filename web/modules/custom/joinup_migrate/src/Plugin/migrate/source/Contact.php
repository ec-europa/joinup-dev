<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

/**
 * Migrates collections.
 *
 * @MigrateSource(
 *   id = "contact"
 * )
 */
class Contact extends JoinupSqlBase {

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
      'uri' => $this->t('URI'),
      'name' => $this->t('Name'),
      'mail' => $this->t('E-mail'),
      'webpage' => $this->t('Webpage'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {

  }

}
