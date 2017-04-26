<?php

/**
 * @file
 * Assertions for 'event' migration.
 */

use Drupal\file\Entity\File;

// Migration counts.
$this->assertTotalCount('event_logo', 1);
$this->assertSuccessCount('event_logo', 1);
$this->assertTotalCount('event', 1);
$this->assertSuccessCount('event', 1);

// Imported content check.
/* @var \Drupal\node\NodeInterface $event */
$event = $this->loadEntityByLabel('node', 'Euritas summit 2015: “Innovate, cooperate, take the challenge!”');
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
$this->assertKeywords(['Cross-border'], $event, 'field_scope');
$this->assertEquals("Rome\nSpazio Europa\nVia IV Novembre, 149\n00187 Rome, Italy", $event->field_location->value);
$image = File::load($event->field_event_logo->target_id);
$this->assertEquals('public://event/logo/logo_euritas_rgb.jpg', $image->getFileUri());
$this->assertEquals('Euritas', $event->field_organisation->value);
$this->assertEquals('Public', $event->field_organisation_type->value);
$this->assertEquals('http://www.euritas.eu/euritas-summit-2015', $event->field_event_web_url->uri);
