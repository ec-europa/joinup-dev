<?php

/**
 * @file
 * Assertions for 'release' migration.
 */

//use Drupal\file\Entity\File;

// Migration counts.
$this->assertTotalCount('release', 8);
$this->assertSuccessCount('release', 8);

// Imported content check.
/* @var \Drupal\rdf_entity\RdfInterface $release */
$release = $this->loadEntityByLabel('rdf_entity', 'cipaedelivery 1.0.0', 'asset_release');
$this->assertEquals('cipaedelivery 1.0.0', $release->label());
$this->assertEquals('asset_release', $release->bundle());
$this->assertEquals('default', $release->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1354298005), $release->field_isr_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1470042243), $release->field_isr_modification_date->value);
$this->assertReferences([
  'RN_CIPAv1.0.0.zip',
  'CipaTestSuite.zip',
  'CIPAPnPAp.zip',
], $release->field_isr_distribution);

$release = $this->loadEntityByLabel('rdf_entity', 'cipaedelivery 1.1.0', 'asset_release');
$this->assertEquals('cipaedelivery 1.1.0', $release->label());
$this->assertEquals('asset_release', $release->bundle());
$this->assertEquals('default', $release->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1370005132), $release->field_isr_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1470042209), $release->field_isr_modification_date->value);
$this->assertReferences([
  'CIPAPNPAp.zip',
  'CIPATestSuite.zip',
], $release->field_isr_distribution);

$release = $this->loadEntityByLabel('rdf_entity', 'cipaedelivery 2.0.0-Access point Beta', 'asset_release');
$this->assertEquals('cipaedelivery 2.0.0-Access point Beta', $release->label());
$this->assertEquals('asset_release', $release->bundle());
$this->assertEquals('default', $release->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1379514961), $release->field_isr_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1470042186), $release->field_isr_modification_date->value);
$this->assertReferences([
  'cipa-as2-access-point-wrapper.war',
  'OpenPEPPOL_AP_CA_production.pem',
], $release->field_isr_distribution);

$release = $this->loadEntityByLabel('rdf_entity', 'cipaedelivery 1.1.1-Certificate issue hot fix', 'asset_release');
$this->assertEquals('cipaedelivery 1.1.1-Certificate issue hot fix', $release->label());
$this->assertEquals('asset_release', $release->bundle());
$this->assertEquals('default', $release->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1381330119), $release->field_isr_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1470042164), $release->field_isr_modification_date->value);
$this->assertReferences(['cipa-start-client-1.1.0_patched.jar'], $release->field_isr_distribution);

$release = $this->loadEntityByLabel('rdf_entity', 'cipaedelivery 2.0.0-Access Point Beta2', 'asset_release');
$this->assertEquals('cipaedelivery 2.0.0-Access Point Beta2', $release->label());
$this->assertEquals('asset_release', $release->bundle());
$this->assertEquals('default', $release->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1385739020), $release->field_isr_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1470042138), $release->field_isr_modification_date->value);
$this->assertReferences([
  'cipa-as2-access-point-wrapper.war',
  'OpenPEPPOL_AP_CA_production.pem',
], $release->field_isr_distribution);

$release = $this->loadEntityByLabel('rdf_entity', 'Cipa e-Delivery', 'asset_release');
$this->assertEquals('Cipa e-Delivery', $release->label());
$this->assertEquals('asset_release', $release->bundle());
$this->assertEquals('default', $release->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1415270105), $release->field_isr_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1470042113), $release->field_isr_modification_date->value);
$this->assertReferences([
  'Release Notes',
  'Cipa Access Point 2.2.3',
], $release->field_isr_distribution);

$release = $this->loadEntityByLabel('rdf_entity', 'CIPA e-Delivery 2.2.4', 'asset_release');
$this->assertEquals('CIPA e-Delivery 2.2.4', $release->label());
$this->assertEquals('asset_release', $release->bundle());
$this->assertEquals('default', $release->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1426068052), $release->field_isr_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1470042087), $release->field_isr_modification_date->value);
$this->assertReferences([
  'e-Delivery VirtualBox 2.2.4',
  'Cipa Access Point 2.2.4',
  'Release Notes 2.2.4',
  'Quick Start Guide',
], $release->field_isr_distribution);

$release = $this->loadEntityByLabel('rdf_entity', 'CEF e-Delivery 3.2.0', 'asset_release');
$this->assertEquals('CEF e-Delivery 3.2.0', $release->label());
$this->assertEquals('asset_release', $release->bundle());
$this->assertEquals('default', $release->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1449843736), $release->field_isr_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1470042058), $release->field_isr_modification_date->value);
$this->assertReferences([
  'cef-edelivery-distribution-3.2.0-alpha-1-as4-jboss',
  'Building Block DSI_IntroDocument (eDelivery)-v1.00',
  'Quick start guide 3.2.0',
  'Release notes 3.2.0',
  'Pmodes Presentation (eDelivery)-v1.00',
], $release->field_isr_distribution);
