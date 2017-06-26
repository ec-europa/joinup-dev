<?php

/**
 * @file
 * Assertions for 'reference' migration.
 */

use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;

$sql = "SELECT body FROM {node_revisions} WHERE nid = :nid ORDER BY vid DESC";
$node = Node::load(76890);
// This file is referenced as file system URL in the body of node 76890.
$file = File::load(30186);

// Before.
$text_before_migration = $this->legacyDb->queryRange($sql, 0, 1, [':nid' => 76890])->fetchField();
$this->assertContains('https://joinup.ec.europa.eu/sites/default/files/list_of_standards_v1.1.xls', $text_before_migration);
$this->assertNotContains('/sites/default/files/custom-page/attachment/list_of_standards_v1.1.xls', $text_before_migration);
$this->assertNotContains($file->uuid(), $text_before_migration);

// After.
$text_after_migration = $node->body->value;
$this->assertNotContains('https://joinup.ec.europa.eu/sites/default/files/list_of_standards_v1.1.xls', $text_after_migration);
$this->assertContains('/sites/default/files/custom-page/attachment/list_of_standards_v1.1.xls', $text_after_migration);
$this->assertContains($file->uuid(), $text_after_migration);
