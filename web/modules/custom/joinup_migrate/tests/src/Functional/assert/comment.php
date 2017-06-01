<?php

/**
 * @file
 * Assertions for 'comment' migration.
 */

use Drupal\comment\Entity\Comment;
use Drupal\file\Entity\File;

// Imported content check. There are more comments imported but we're interested
// only in this, in order to check the attached file.
$cids = \Drupal::entityQuery('comment')
  ->condition('comment_type', 'reply')
  ->condition('subject', '#11')
  ->condition('created', 1433885762)
  ->condition('hostname', '83.99.4.2')
  ->execute();
$cid = reset($cids);
$comment = Comment::load($cid);
$this->assertEquals('#11', $comment->label());
$this->assertEquals('reply', $comment->bundle());
$this->assertEquals(1433885762, $comment->getCreatedTime());
$this->assertEquals(1433885762, $comment->getChangedTime());
$this->assertEquals('83.99.4.2', $comment->getHostname());
$this->assertEquals('gemerwi', $comment->getAuthorName());
$this->assertEquals('0b/', $comment->getThread());
$this->assertEquals('field_replies', $comment->getFieldName());
$this->assertEquals(0, $comment->getOwnerId());
$this->assertEquals(0, $comment->getOwnerId());
$this->assertContains("new concepts can be added (quickly) if needed.", $comment->get('field_body')->value);
$file = File::load($comment->field_attachment->target_id);
$this->assertEquals('public://discussion/attachment/frequencies-skos.rdf_.zip', $file->getFileUri());
