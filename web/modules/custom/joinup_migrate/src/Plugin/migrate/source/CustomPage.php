<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\joinup_migrate\RedirectImportInterface;
use Drupal\migrate\Row;

/**
 * Migrates custom pages.
 *
 * @MigrateSource(
 *   id = "custom_page"
 * )
 */
class CustomPage extends JoinupSqlBase implements RedirectImportInterface {

  use DefaultRedirectTrait;

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
      'collection' => $this->t('Collection'),
      'fids' => $this->t('Attachments'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_custom_page', 'n')->fields('n', [
      'nid',
      'vid',
      'type',
      'title',
      'created',
      'changed',
      'uid',
      'body',
      'collection',
      'group_nid',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $fids = $this->select('upload', 'u')
      ->fields('u', ['fid'])
      ->condition('u.vid', $row->getSourceProperty('vid'))
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('fids', $fids);

    return parent::prepareRow($row);
  }

}
