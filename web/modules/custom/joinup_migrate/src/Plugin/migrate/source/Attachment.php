<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Migrates Discussion, Event and News attachments.
 *
 * @MigrateSource(
 *   id = "attachment"
 * )
 */
class Attachment extends JoinupSqlBase {

  /**
   * Type map.
   *
   * @var array
   */
  protected static $typeMap = [
    'project_issue' => 'discussion',
    'event' => 'event',
    'news' => 'news',
  ];

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 'a',
      ],
      'delta' => [
        'type' => 'integer',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'type' => $this->t('Parent node type'),
      'nid' => $this->t('Parent node ID'),
      'vid' => $this->t('Parent node revision ID'),
      'delta' => $this->t('Field item delta'),
      'fid' => $this->t('File ID'),
      'path' => $this->t('File path'),
      'timestamp' => $this->t('Created time'),
      'uid' => $this->t('File owner'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_attachment', 'a')
      ->fields('a', array_keys($this->fields()));
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Assure a full-qualified path.
    $source_path = $this->getLegacySiteWebRoot() . '/' . $row->getSourceProperty('path');
    $row->setSourceProperty('path', $source_path);

    // Build the destination URI.
    $basename = basename($source_path);
    $type = static::$typeMap[$row->getSourceProperty('type')];
    $destination_uri = "public://$type/attachment/$basename";
    $row->setSourceProperty('destination_uri', $destination_uri);

    return parent::prepareRow($row);
  }

}
