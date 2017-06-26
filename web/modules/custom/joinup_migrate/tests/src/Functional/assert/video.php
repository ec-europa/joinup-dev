<?php

/**
 * @file
 * Assertions for 'video' migration.
 */

use Drupal\node\Entity\Node;

// Migration counts.
$this->assertTotalCount('video', 1);
$this->assertSuccessCount('video', 1);

// Imported content check.
$video = Node::load(125838);
$this->assertEquals('Poznan event: ePractice TV interview: Wojciech Cellary, Poznan University of Economics ', $video->label());
$this->assertEquals('video', $video->bundle());
$this->assertEquals(1325763434, $video->created->value);
$this->assertEquals(1326443718, $video->changed->value);
$this->assertEquals('https://www.youtube.com/watch?v=VFvkKvSg4Ek', $video->field_video->value);
$this->assertContains('Interviewer: Vassilia Orfanou', $video->body->value);
$this->assertKeywords([
  'eGovernment',
  'Other',
  '6th Ministerial Conference on Borderless eGovernment',
], $video);
$this->assertReferences(['Poland'], $video->field_video_spatial_coverage);
/* @var \Drupal\rdf_entity\RdfInterface $collection */
$collection = $this->loadEntityByLabel('rdf_entity', 'Collection with 1 entity having custom section', 'collection');
$this->assertEquals($collection->id(), $video->og_audience->target_id);
$this->assertRedirects(['community/epractice/video/poznan-event-epractice-tv-interview-wojciech-cellary-poznan-university-eco'], $video);
