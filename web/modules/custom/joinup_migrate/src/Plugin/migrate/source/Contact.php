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
  protected $reservedUriTables = ['collection', 'solution', 'release', 'distribution'];

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 'c',
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
      'title' => $this->t('Name'),
      'mail' => $this->t('E-mail'),
      'webpage' => $this->t('Webpage'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_contact', 'c')
      ->distinct()
      ->fields('c', ['nid', 'vid', 'uri', 'title', 'mail', 'webpage']);
  }

}
