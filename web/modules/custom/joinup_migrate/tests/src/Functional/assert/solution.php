<?php

/**
 * @file
 * Assertions for 'solution' migration.
 */

use Drupal\file\Entity\File;

// Migration counts.
$this->assertTotalCount('solution', 3);
$this->assertSuccessCount('solution', 3);

// Imported content check.
/* @var \Drupal\rdf_entity\RdfInterface $solution */
$solution = $this->loadEntityByLabel('rdf_entity', 'The administrative geography and civil voting area ontology', 'solution');
$this->assertEquals('The administrative geography and civil voting area ontology', $solution->label());
$this->assertEquals('solution', $solution->bundle());
$this->assertEquals('draft', $solution->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1270080000), $solution->field_is_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1449493447), $solution->field_is_modification_date->value);
$this->assertReferences([
  'The administrative geography and civil voting area ontology',
], $solution->field_is_distribution);
$this->assertReferences(['eProcurement'], $solution->field_policy_domain);
$this->assertEquals("<p>An ontology describing the administrative and voting area geography of Great Britain</p>\r\n", $solution->field_is_description->value);
$this->assertEquals('content_editor', $solution->field_is_description->format);
$this->assertEquals(1, $solution->field_is_elibrary_creation->value);
/* @var \Drupal\rdf_entity\RdfInterface $owner */
$owner = $this->loadEntityByLabel('rdf_entity', 'Ordnance Survey', 'owner');
$this->assertEquals($owner->id(), $solution->field_is_owner->target_id);
$this->assertReferences(['Completed'], $solution->get('field_status'));
$this->assertEquals('proposed', $solution->field_is_state->value);

$solution = $this->loadEntityByLabel('rdf_entity', 'CIPA e-Delivery', 'solution');
$this->assertEquals('CIPA e-Delivery', $solution->label());
$this->assertEquals('solution', $solution->bundle());
$this->assertEquals('default', $solution->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1341505914), $solution->field_is_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1467363502), $solution->field_is_modification_date->value);
$this->assertReferences([
  'CEF e-Delivery 3.2.0',
  'CIPA e-Delivery 2.2.4',
  'Cipa e-Delivery',
  'cipaedelivery 2.0.0-Access Point Beta2',
  'cipaedelivery 1.1.1-Certificate issue hot fix',
  'cipaedelivery 2.0.0-Access point Beta',
  'cipaedelivery 1.1.0',
  'cipaedelivery 1.0.0',
], $solution->field_is_has_version);
$this->assertReferences(['eProcurement'], $solution->field_policy_domain);
$this->assertStringEndsWith("and the <a href=\"http://www.esens.eu/technical-solutions/e-sens-competence-clusters/e-delivery/\">eSENS eDelivery</a> building blocks.</p>\r\n", $solution->field_is_description->value);
$this->assertEquals('content_editor', $solution->field_is_description->format);
$this->assertEquals(1, $solution->field_is_elibrary_creation->value);
$logo = File::load($solution->field_is_logo->target_id);
$this->assertEquals('public://solution/logo/CIPA_e-Delivery_70x70.png', $logo->getFileUri());
$this->assertFileExists('public://solution/logo/CIPA_e-Delivery_70x70.png');
$this->assertEquals(1435, filesize('public://solution/logo/CIPA_e-Delivery_70x70.png'));
$this->assertTrue($solution->get('field_status')->isEmpty());
$this->assertEquals('validated', $solution->field_is_state->value);

$solution = $this->loadEntityByLabel('rdf_entity', 'Styles Layer Descriptor', 'solution');
$this->assertEquals('Styles Layer Descriptor', $solution->label());
$this->assertEquals('solution', $solution->bundle());
$this->assertEquals('default', $solution->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1393346353), $solution->field_is_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1424438316), $solution->field_is_modification_date->value);
$this->assertTrue($solution->get('field_is_has_version')->isEmpty());
$this->assertReferences(['Styles Layer Descriptor'], $solution->field_is_distribution);
$this->assertReferences(['Open government'], $solution->field_policy_domain);
$this->assertStringEndsWith("user-defined symbols and colors to be used in geographic information.</p>\r\n", $solution->field_is_description->value);
$this->assertEquals('content_editor', $solution->field_is_description->format);
$this->assertEquals(1, $solution->field_is_elibrary_creation->value);
$this->assertReferences(['Completed'], $solution->get('field_status'));
$this->assertEquals('validated', $solution->field_is_state->value);
