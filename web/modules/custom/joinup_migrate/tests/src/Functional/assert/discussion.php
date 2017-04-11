<?php

/**
 * @file
 * Assertions for 'discussion' migration.
 */

// Migration counts.
$this->assertTotalCount('discussion', 4);
$this->assertSuccessCount('discussion', 4);

// Imported content check.
/* @var \Drupal\rdf_entity\RdfInterface $solution */
$solution = $this->loadEntityByLabel('rdf_entity', 'CIPA e-Delivery');

/* @var \Drupal\node\NodeInterface $discussion */
$discussion = $this->loadEntityByLabel('node', 'URL for SML and SMK');
$this->assertEquals('URL for SML and SMK', $discussion->label());
$this->assertEquals('discussion', $discussion->bundle());
$this->assertEquals(1453193673, $discussion->created->value);
$this->assertEquals(1453193673, $discussion->changed->value);
$this->assertEquals(1, $discussion->uid->target_id);
$this->assertContains('<h2>Component</h2>', $discussion->body->value);
$this->assertContains('<h2>Category</h2>', $discussion->body->value);
$this->assertTrue($discussion->isPublished());
$this->assertEquals($solution->id(), $discussion->og_audience->target_id);

$discussion = $this->loadEntityByLabel('node', "Spaces in 'Apache Tomcat 6.0.16' cause problems under linux");
$this->assertEquals("Spaces in 'Apache Tomcat 6.0.16' cause problems under linux", $discussion->label());
$this->assertEquals('discussion', $discussion->bundle());
$this->assertEquals(1370316710, $discussion->created->value);
$this->assertEquals(1372268368, $discussion->changed->value);
$this->assertEquals(1, $discussion->uid->target_id);
$this->assertContains('<h2>Component</h2>', $discussion->body->value);
$this->assertContains('<h2>Category</h2>', $discussion->body->value);
$this->assertFalse($discussion->isPublished());
$this->assertEquals($solution->id(), $discussion->og_audience->target_id);

$discussion = $this->loadEntityByLabel('node', 'cipa-smp-webapp is not thread safe');
$this->assertEquals('cipa-smp-webapp is not thread safe', $discussion->label());
$this->assertEquals('discussion', $discussion->bundle());
$this->assertEquals(1364378601, $discussion->created->value);
$this->assertEquals(1373569684, $discussion->changed->value);
$this->assertEquals(1, $discussion->uid->target_id);
$this->assertContains('<h2>Component</h2>', $discussion->body->value);
$this->assertContains('<h2>Category</h2>', $discussion->body->value);
$this->assertFalse($discussion->isPublished());
$this->assertEquals($solution->id(), $discussion->og_audience->target_id);

$discussion = $this->loadEntityByLabel('node', 'cipa-smp-client-console still only use START protocol');
$this->assertEquals('cipa-smp-client-console still only use START protocol', $discussion->label());
$this->assertEquals('discussion', $discussion->bundle());
$this->assertEquals(1431075859, $discussion->created->value);
$this->assertEquals(1431075989, $discussion->changed->value);
$this->assertEquals(1, $discussion->uid->target_id);
$this->assertContains('<h2>Component</h2>', $discussion->body->value);
$this->assertContains('<h2>Category</h2>', $discussion->body->value);
$this->assertTrue($discussion->isPublished());
$this->assertEquals($solution->id(), $discussion->og_audience->target_id);
