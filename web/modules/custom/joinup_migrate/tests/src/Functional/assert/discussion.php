<?php

/**
 * @file
 * Assertions for 'discussion' migration.
 */

// Migration counts.
$this->assertTotalCount('discussion', 455);
$this->assertSuccessCount('discussion', 455);

// Imported content check.
/* @var \Drupal\rdf_entity\RdfInterface $solution */
$solution = $this->loadEntityByLabel('rdf_entity', 'CIPA e-Delivery', 'solution');

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
$this->assertEquals('validated', $discussion->field_state->value);
$this->assertRedirects(['software/cipaedelivery/issue/url-sml-and-smk'], $discussion);

$discussion = $this->loadEntityByLabel('node', "Spaces in 'Apache Tomcat 6.0.16' cause problems under linux");
$this->assertEquals("Spaces in 'Apache Tomcat 6.0.16' cause problems under linux", $discussion->label());
$this->assertEquals('discussion', $discussion->bundle());
$this->assertEquals(1370316710, $discussion->created->value);
$this->assertEquals(1372268368, $discussion->changed->value);
$this->assertEquals(1, $discussion->uid->target_id);
$this->assertContains('<h2>Component</h2>', $discussion->body->value);
$this->assertContains('<h2>Category</h2>', $discussion->body->value);
$this->assertTrue($discussion->isPublished());
$this->assertEquals($solution->id(), $discussion->og_audience->target_id);
$this->assertEquals('validated', $discussion->field_state->value);
$this->assertRedirects(['software/cipaedelivery/issue/spaces-apache-tomcat-6016-cause-problems-under-linux'], $discussion);

$discussion = $this->loadEntityByLabel('node', 'cipa-smp-webapp is not thread safe');
$this->assertEquals('cipa-smp-webapp is not thread safe', $discussion->label());
$this->assertEquals('discussion', $discussion->bundle());
$this->assertEquals(1364378601, $discussion->created->value);
$this->assertEquals(1373569684, $discussion->changed->value);
$this->assertEquals(1, $discussion->uid->target_id);
$this->assertContains('<h2>Component</h2>', $discussion->body->value);
$this->assertContains('<h2>Category</h2>', $discussion->body->value);
$this->assertTrue($discussion->isPublished());
$this->assertEquals($solution->id(), $discussion->og_audience->target_id);
$this->assertEquals('validated', $discussion->field_state->value);
$this->assertRedirects(['software/cipaedelivery/issue/cipa-smp-webapp-not-thread-safe'], $discussion);

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
$this->assertEquals('validated', $discussion->field_state->value);
$this->assertRedirects(['software/cipaedelivery/issue/cipa-smp-client-console-still-only-use-start-protocol'], $discussion);

// There are 44 discussions in Solution 'Core Location Vocabulary' but we test
// only 'Format issue (release 1.00)' because we want to check how attachments
// were migrated. Also the solution 'DCAT application profile for data portals
// in Europe' is creating 251 discussions but we don't test them here because
// they are valuable for 'comment' and 'comment_file' migration, they contain
// comments with attachments.
$solution = $this->loadEntityByLabel('rdf_entity', 'Core Location Vocabulary', 'solution');
$discussion = $this->loadEntityByLabel('node', 'Format issue (release 1.00)', 'discussion');
$this->assertEquals('Format issue (release 1.00)', $discussion->label());
$this->assertEquals('discussion', $discussion->bundle());
$this->assertEquals(1356010265, $discussion->created->value);
$this->assertEquals(1360231478, $discussion->changed->value);
$this->assertEquals(1, $discussion->uid->target_id);
$this->assertContains('<h2>Component</h2>', $discussion->body->value);
$this->assertContains('<h2>Category</h2>', $discussion->body->value);
$this->assertTrue($discussion->isPublished());
$this->assertEquals($solution->id(), $discussion->og_audience->target_id);
$this->assertEquals('validated', $discussion->field_state->value);
$this->assertReferences([
  'CoreLocTBCProblem.png',
  'locn-v1.00-afterTBC load.rdf_.txt',
], $discussion->field_attachment);
$this->assertRedirects(['asset/core_location/issue/format-issue-release-100'], $discussion);
