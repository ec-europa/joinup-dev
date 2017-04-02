<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

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
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_comment', 'c')->fields('c');
  }

}
