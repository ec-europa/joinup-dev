<?php

/**
 * @file
 * Assertions for 'event' migration.
 */

use Drupal\file\Entity\File;

// Migration counts.
$this->assertTotalCount('event_logo', 1);
$this->assertSuccessCount('event_logo', 1);
$this->assertTotalCount('event', 2);
$this->assertSuccessCount('event', 2);

// Imported content check.
/* @var \Drupal\node\NodeInterface $event */
$event = $this->loadEntityByLabel('node', 'Euritas summit 2015: “Innovate, cooperate, take the challenge!”', 'event');
$this->assertEquals('Euritas summit 2015: “Innovate, cooperate, take the challenge!”', $event->label());
$this->assertEquals('event', $event->bundle());
$this->assertEquals(1440160716, $event->created->value);
$this->assertEquals(1440161179, $event->changed->value);
$this->assertEquals(1, $event->uid->target_id);
$this->assertEquals("<p><a href=\"http://www.euritas.eu/euritas-summit-2015/agenda\">http://www.euritas.eu/euritas-summit-2015/agenda</a></p>\r\n", $event->field_event_agenda->value);
$this->assertEquals('gov-it.eu@brz.gv.at', $event->field_event_contact_email->value);
$this->assertStringEndsWith("<div>Expected Participants: <p>150 representatives from the public sector</p>\r\n</div>\n<div>State: Pending</div>", $event->body->value);
$this->assertEquals('2015-10-15T08:30:00', $event->field_event_start_date->value);
$this->assertEquals('2015-10-16T13:00:00', $event->field_event_end_date->value);
$this->assertEquals($new_collection->id(), $event->og_audience->target_id);
$this->assertKeywords([
  'cooperation',
  'eGovernment',
  'Innovation',
  'IT in public sector',
  'Public Administration',
], $event);
$this->assertKeywords([
  'cross_border',
  'european',
  'local',
  'national',
  'pan_european',
], $event, 'field_scope');
$this->assertEquals("Rome\nSpazio Europa\nVia IV Novembre, 149\n00187 Rome, Italy", $event->field_location->value);
$image = File::load($event->field_event_logo->target_id);
$this->assertEquals('public://event/logo/logo_euritas_rgb.jpg', $image->getFileUri());
$this->assertEquals('Euritas', $event->field_organisation->value);
$this->assertEquals('public', $event->field_organisation_type->value);
$this->assertEquals('http://www.euritas.eu/euritas-summit-2015', $event->field_event_web_url->uri);
$this->assertEquals('proposed', $event->field_state->value);

$event = $this->loadEntityByLabel('node', 'CPSV-AP Revision WG Virtual Meeting 3', 'event');
$this->assertEquals('CPSV-AP Revision WG Virtual Meeting 3', $event->label());
$this->assertEquals('event', $event->bundle());
$this->assertEquals(1458817102, $event->created->value);
$this->assertEquals(1464677770, $event->changed->value);
$this->assertEquals(1, $event->uid->target_id);
$this->assertTrue($event->get('field_event_agenda')->isEmpty());
$this->assertTrue($event->get('field_event_contact_email')->isEmpty());
$this->assertStringEndsWith("<div>Expected Participants: <p>Members of the CPSV-AP Revision WG</p>\r\n</div>\n<div>State: Pending</div>", $event->body->value);
$this->assertEquals('2016-05-24T09:00:00', $event->field_event_start_date->value);
$this->assertEquals('2016-05-24T11:00:00', $event->field_event_end_date->value);
$collection = $this->loadEntityByLabel('rdf_entity', 'Archived collection', 'collection');
$this->assertEquals($collection->id(), $event->og_audience->target_id);
$this->assertKeywords([
  'Interoperability',
  'application profile',
  'cpsv',
], $event);
$this->assertTrue($event->get('field_scope')->isEmpty());
$this->assertEquals('Virtual Meeting', $event->field_location->value);
$this->assertEquals('ISA Programme', $event->field_organisation->value);
$this->assertTrue($event->get('field_event_web_url')->isEmpty());
$this->assertEquals('proposed', $event->field_state->value);
