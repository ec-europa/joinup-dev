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
  protected $reservedUriTables = ['collection', 'solution', 'release', 'distribution'];

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 'l',
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
      'uri' => $this->t('URI'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_licence', 'l')
      ->distinct()
      ->fields('l', ['nid', 'title', 'body', 'type', 'uri']);
  }

}
