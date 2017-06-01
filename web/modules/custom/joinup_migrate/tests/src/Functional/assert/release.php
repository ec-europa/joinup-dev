<?php

/**
 * @file
 * Assertions for 'release' migration.
 */

// Migration counts.
$this->assertTotalCount('release', 57);
$this->assertSuccessCount('release', 57);

// Imported content check.
/* @var \Drupal\rdf_entity\RdfInterface $release */
$release = $this->loadEntityByLabel('rdf_entity', 'cipaedelivery 1.0.0', 'asset_release');
$this->assertEquals('cipaedelivery 1.0.0', $release->label());
$this->assertEquals('asset_release', $release->bundle());
$this->assertEquals('draft', $release->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1354298005), $release->field_isr_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1470042243), $release->field_isr_modification_date->value);
$this->assertReferences([
  'RN_CIPAv1.0.0.zip',
  'CipaTestSuite.zip',
  'CIPAPnPAp.zip',
], $release->field_isr_distribution);
$this->assertTrue($release->get('field_status')->isEmpty());
$this->assertEquals('draft', $release->field_isr_state->value);

$release = $this->loadEntityByLabel('rdf_entity', 'cipaedelivery 1.1.0', 'asset_release');
$this->assertEquals('cipaedelivery 1.1.0', $release->label());
$this->assertEquals('asset_release', $release->bundle());
$this->assertEquals('draft', $release->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1370005132), $release->field_isr_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1470042209), $release->field_isr_modification_date->value);
$this->assertReferences([
  'CIPAPNPAp.zip',
  'CIPATestSuite.zip',
], $release->field_isr_distribution);
$this->assertTrue($release->get('field_status')->isEmpty());
$this->assertEquals('draft', $release->field_isr_state->value);

$release = $this->loadEntityByLabel('rdf_entity', 'cipaedelivery 2.0.0-Access point Beta', 'asset_release');
$this->assertEquals('cipaedelivery 2.0.0-Access point Beta', $release->label());
$this->assertEquals('asset_release', $release->bundle());
$this->assertEquals('draft', $release->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1379514961), $release->field_isr_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1470042186), $release->field_isr_modification_date->value);
$this->assertReferences([
  'cipa-as2-access-point-wrapper.war',
  'OpenPEPPOL_AP_CA_production.pem',
], $release->field_isr_distribution);
$this->assertTrue($release->get('field_status')->isEmpty());
$this->assertEquals('draft', $release->field_isr_state->value);

$release = $this->loadEntityByLabel('rdf_entity', 'cipaedelivery 1.1.1-Certificate issue hot fix', 'asset_release');
$this->assertEquals('cipaedelivery 1.1.1-Certificate issue hot fix', $release->label());
$this->assertEquals('asset_release', $release->bundle());
$this->assertEquals('draft', $release->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1381330119), $release->field_isr_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1470042164), $release->field_isr_modification_date->value);
$this->assertReferences(['cipa-start-client-1.1.0_patched.jar'], $release->field_isr_distribution);
$this->assertTrue($release->get('field_status')->isEmpty());
$this->assertEquals('draft', $release->field_isr_state->value);

$release = $this->loadEntityByLabel('rdf_entity', 'cipaedelivery 2.0.0-Access Point Beta2', 'asset_release');
$this->assertEquals('cipaedelivery 2.0.0-Access Point Beta2', $release->label());
$this->assertEquals('asset_release', $release->bundle());
$this->assertEquals('draft', $release->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1385739020), $release->field_isr_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1470042138), $release->field_isr_modification_date->value);
$this->assertReferences([
  'cipa-as2-access-point-wrapper.war',
  'OpenPEPPOL_AP_CA_production.pem',
], $release->field_isr_distribution);
$this->assertTrue($release->get('field_status')->isEmpty());
$this->assertEquals('draft', $release->field_isr_state->value);

$release = $this->loadEntityByLabel('rdf_entity', 'Cipa e-Delivery', 'asset_release');
$this->assertEquals('Cipa e-Delivery', $release->label());
$this->assertEquals('asset_release', $release->bundle());
$this->assertEquals('draft', $release->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1415270105), $release->field_isr_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1470042113), $release->field_isr_modification_date->value);
$this->assertReferences([
  'Release Notes',
  'Cipa Access Point 2.2.3',
], $release->field_isr_distribution);
$this->assertTrue($release->get('field_status')->isEmpty());
$this->assertEquals('draft', $release->field_isr_state->value);

$release = $this->loadEntityByLabel('rdf_entity', 'CIPA e-Delivery 2.2.4', 'asset_release');
$this->assertEquals('CIPA e-Delivery 2.2.4', $release->label());
$this->assertEquals('asset_release', $release->bundle());
$this->assertEquals('draft', $release->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1426068052), $release->field_isr_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1470042087), $release->field_isr_modification_date->value);
$this->assertReferences([
  'e-Delivery VirtualBox 2.2.4',
  'Cipa Access Point 2.2.4',
  'Release Notes 2.2.4',
  'Quick Start Guide',
], $release->field_isr_distribution);
$this->assertTrue($release->get('field_status')->isEmpty());
$this->assertEquals('draft', $release->field_isr_state->value);

$release = $this->loadEntityByLabel('rdf_entity', 'CEF e-Delivery 3.2.0', 'asset_release');
$this->assertEquals('CEF e-Delivery 3.2.0', $release->label());
$this->assertEquals('asset_release', $release->bundle());
$this->assertEquals('draft', $release->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1449843736), $release->field_isr_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1470042058), $release->field_isr_modification_date->value);
$this->assertReferences([
  'cef-edelivery-distribution-3.2.0-alpha-1-as4-jboss',
  'Building Block DSI_IntroDocument (eDelivery)-v1.00',
  'Quick start guide 3.2.0',
  'Release notes 3.2.0',
  'Pmodes Presentation (eDelivery)-v1.00',
], $release->field_isr_distribution);
$this->assertReferences(['Completed'], $release->get('field_status'));
$this->assertEquals('draft', $release->field_isr_state->value);

$release = $this->loadEntityByLabel('rdf_entity', 'Core Location Vocabulary 0.2', 'asset_release');
$this->assertEquals('Core Location Vocabulary 0.2', $release->label());
$this->assertEquals('asset_release', $release->bundle());
$this->assertEquals('draft', $release->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1329468540), $release->field_isr_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1458817654), $release->field_isr_modification_date->value);
$this->assertReferences([
  'core_vocabularies-location_issues_raised_within_working_group-v02zip',
  'core_location_vocabulary_use-cases-v02zip',
  'core_vocabularies-business_location_person-specification-v02zip',
  'core_vocabularies-business_location_person-xml_schema-v02zip',
  'location_rdf_schema-v01zip',
], $release->field_isr_distribution);
$this->assertReferences(['Deprecated'], $release->get('field_status'));
$this->assertEquals('draft', $release->field_isr_state->value);

$release = $this->loadEntityByLabel('rdf_entity', 'Core Location Vocabulary 0.3', 'asset_release');
$this->assertEquals('Core Location Vocabulary 0.3', $release->label());
$this->assertEquals('asset_release', $release->bundle());
$this->assertEquals('draft', $release->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1335801026), $release->field_isr_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1458840630), $release->field_isr_modification_date->value);
$this->assertReferences([
  'core_vocabularies-business_location_person-specification-v03zip',
], $release->field_isr_distribution);
$this->assertReferences(['Deprecated'], $release->get('field_status'));
$this->assertEquals('draft', $release->field_isr_state->value);

$release = $this->loadEntityByLabel('rdf_entity', 'Core Location Vocabulary 1.00', 'asset_release');
$this->assertEquals('Core Location Vocabulary 1.00', $release->label());
$this->assertEquals('asset_release', $release->bundle());
$this->assertEquals('draft', $release->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1336376126), $release->field_isr_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1467128099), $release->field_isr_modification_date->value);
$this->assertReferences([
  'Core_Vocabularies-Location_Issues_Raised_within_Working_Group-v1.00.zip',
  'Core_Vocabularies-Business_Location_Person_v1.00_Conceptual_Model_0.zip',
  'CoreLocation-v1.00.xsd.html',
  'core_vocabularies-location_v100_specification_pdf',
  'locn-v1.00.rdf.html',
  'locn-v1.00.rdf',
  'CoreLocation-v1.00.xsd',
  'Core_Vocabularies-Business_Location_Person_v1.00_RDF_Schema_zip',
  'Core_Vocabularies-Business_Location_Person_v1.00_Specification_zip',
], $release->field_isr_distribution);
$this->assertReferences(['Completed'], $release->get('field_status'));
$this->assertEquals('draft', $release->field_isr_state->value);
