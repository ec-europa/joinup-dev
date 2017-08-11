<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Migrates comments.
 *
 * @MigrateSource(
 *   id = "comment"
 * )
 */
class Comment extends JoinupSqlBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'cid' => [
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
      'nid' => $this->t('Parent node ID'),
      'type' => $this->t('Parent node type'),
      'cid' => $this->t('ID'),
      'pid' => $this->t('Parent ID'),
      'uid' => $this->t('User ID'),
      'status' => $this->t('Status'),
      'subject' => $this->t('Subject'),
      'comment' => $this->t('Body'),
      'thread' => $this->t('Thread'),
      'timestamp' => $this->t('Timestamp'),
      'name' => $this->t('Author name'),
      'mail' => $this->t('Author mail'),
      'homepage' => $this->t('Author homepage'),
      'hostname' => $this->t('Hostname'),
      'comment_type' => $this->t('Comment type'),
      'field_name' => $this->t('Field name'),
      'fids' => $this->t('Attached file IDs'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_comment', 'c')->fields('c');
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Comment attachments, if case.
    if ($row->getSourceProperty('type') === 'project_issue') {
      $fids = $this->select('comment_upload', 'u')
        ->fields('u', ['fid'])
        ->condition('u.cid', $row->getSourceProperty('cid'))
        ->execute()
        ->fetchCol();
      if ($fids) {
        $row->setSourceProperty('fids', $fids);
      }
    }

    return parent::prepareRow($row);
  }

}
