<?php

/**
 * @file
 * Assertions for 'event' migration.
 */

use Drupal\file\Entity\File;

// Migration counts.
$this->assertTotalCount('file__event_logo', 2);
$this->assertSuccessCount('file__event_logo', 2);
$this->assertTotalCount('event', 3);
$this->assertSuccessCount('event', 3);

// Imported content check.
/* @var \Drupal\node\NodeInterface $event */
$event = $this->loadEntityByLabel('node', 'Euritas summit 2015: “Innovate, cooperate, take the challenge!”', 'event');
$this->assertEquals('Euritas summit 2015: “Innovate, cooperate, take the challenge!”', $event->label());
$this->assertEquals('event', $event->bundle());
$this->assertEquals(1440160716, $event->created->value);
$this->assertEquals(1440161179, $event->changed->value);
$this->assertEquals(1, $event->uid->target_id);
$this->assertContains('http://www.euritas.eu/euritas-summit-2015/agenda', $event->field_event_agenda->value);
$this->assertEquals('gov-it.eu@brz.gv.at', $event->field_event_contact_email->value);
$this->assertContains('150 representatives from the public sector', $event->body->value);
$this->assertEquals('2015-10-15T08:30:00', $event->field_event_date->value);
$this->assertEquals('2015-10-16T13:00:00', $event->field_event_date->end_value);
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
$this->assertReferences(static::$europeCountries, $event->field_event_spatial_coverage);
$this->assertRedirects(['community/egovernment/event/euritas-summit-2015-“innovate-cooperate-take-challenge”-0'], $event);

$event = $this->loadEntityByLabel('node', 'CPSV-AP Revision WG Virtual Meeting 3', 'event');
$this->assertEquals('CPSV-AP Revision WG Virtual Meeting 3', $event->label());
$this->assertEquals('event', $event->bundle());
$this->assertEquals(1458817102, $event->created->value);
$this->assertEquals(1464677770, $event->changed->value);
$this->assertEquals(1, $event->uid->target_id);
$this->assertTrue($event->get('field_event_agenda')->isEmpty());
$this->assertTrue($event->get('field_event_contact_email')->isEmpty());
$this->assertContains('Members of the CPSV-AP Revision WG', $event->body->value);
$this->assertEquals('2016-05-24T09:00:00', $event->field_event_date->value);
$this->assertEquals('2016-05-24T11:00:00', $event->field_event_date->end_value);
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
$this->assertReferences(static::$europeCountries, $event->field_event_spatial_coverage);
$this->assertRedirects(['asset/cpsv-ap/event/cpsv-ap-revision-wg-virtual-meeting-0'], $event);

$event = $this->loadEntityByLabel('node', '5th International Workshop on e-Health in Emerging Economies - IWEEE Granada -', 'event');
$this->assertEquals('5th International Workshop on e-Health in Emerging Economies - IWEEE Granada -', $event->label());
$this->assertEquals('event', $event->bundle());
$this->assertEquals(1323441667, $event->created->value);
$this->assertEquals(1323806587, $event->changed->value);
$this->assertTrue($event->get('field_event_agenda')->isEmpty());
$this->assertEquals('info@iweee.org', $event->get('field_event_contact_email')->value);
$this->assertContains('Thymbra', $event->body->value);
$this->assertEquals('2012-01-11T09:00:00', $event->field_event_date->value);
$this->assertEquals('2012-01-11T18:00:00', $event->field_event_date->end_value);
$collection = $this->loadEntityByLabel('rdf_entity', 'Collection with 1 entity having custom section', 'collection');
$this->assertEquals($collection->id(), $event->og_audience->target_id);
$this->assertKeywords([
  'e-health',
  'emerging economies',
  'free software',
  'Health',
  'open source',
], $event);
$this->assertKeywords(['international'], $event, 'field_scope');
$this->assertEquals('Granada, Spain', $event->field_location->value);
$image = File::load($event->field_event_logo->target_id);
$this->assertEquals('public://event/logo/logo_gnu_solidario.png', $image->getFileUri());
$this->assertEquals('GNU Solidario', $event->field_organisation->value);
$this->assertEquals('http://www.iweee.org', $event->get('field_event_web_url')->uri);
$this->assertEquals('validated', $event->field_state->value);
$this->assertReferences(['Spain'], $event->field_event_spatial_coverage);
$this->assertRedirects(['event/5th-international-workshop-e-health-emerging-economies-iweee-granada'], $event);
