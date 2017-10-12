<?php

/**
 * @file
 * Assertions for 'newsletter' migration.
 */

use Drupal\node\Entity\Node;

// Imported content check.
/* @var \Drupal\node\NodeInterface $newsletter */
$newsletter = Node::load(152066);
$this->assertEquals('Joinup Open Source News Service - June 2016', $newsletter->label());
$this->assertEquals('newsletter', $newsletter->bundle());
$this->assertEquals(1465386690, $newsletter->created->value);
$user = user_load_by_name('user6363');
$this->assertEquals($user->id(), $newsletter->uid->target_id);
$this->assertContains('position of the European Union.', $newsletter->body->value);
$this->assertReferences(['New collection'], $newsletter->get('og_audience'));
$this->assertRedirects(['newsletter/joinup-open-source-news-service-june-2016'], $newsletter);
