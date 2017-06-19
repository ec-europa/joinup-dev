<?php

/**
 * @file
 * Assertions for 'newsletter' migration.
 */

// Migration counts.
$this->assertTotalCount('newsletter', 1);
$this->assertSuccessCount('newsletter', 1);

// Imported content check.
/* @var \Drupal\node\NodeInterface $newsletter */
$newsletter = $this->loadEntityByLabel('node', 'Joinup Open Source News Service - June 2016');
$this->assertEquals('Joinup Open Source News Service - June 2016', $newsletter->label());
$this->assertEquals('newsletter', $newsletter->bundle());
$this->assertEquals(1465386690, $newsletter->created->value);
$this->assertEquals(1465464416, $newsletter->changed->value);
$user = user_load_by_name('joinup_editor');
$this->assertEquals($user->id(), $newsletter->uid->target_id);
$this->assertContains('position of the European Union.', $newsletter->body->value);
$this->assertRedirects(['newsletter/joinup-open-source-news-service-june-2016'], $newsletter);
