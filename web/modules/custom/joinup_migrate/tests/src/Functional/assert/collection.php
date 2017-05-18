<?php

/**
 * @file
 * Assertions for 'collection' migration.
 */

use Drupal\file\Entity\File;

// Migration counts.
$this->assertTotalCount('collection', 5);
$this->assertSuccessCount('collection', 5);

// Imported content check.
/* @var \Drupal\rdf_entity\RdfInterface $collection */
$collection = $new_collection;
$this->assertEquals('New collection', $collection->label());
$this->assertEquals('collection', $collection->bundle());
// This collection is new, thus its creation and modification time is the
// migration time. We approximately check those values by assuming that the
// migration ran in the last 20 minutes.
$migration_time = gmdate('Y-m-d\TH:i:s', \Drupal::time()->getRequestTime() - 20 * 60);
$this->assertGreaterThan($migration_time, $collection->field_ar_creation_date->value);
$this->assertGreaterThan($migration_time, $collection->field_ar_modification_date->value);
$this->assertEquals('default', $collection->graph->value);
$this->assertReferences([
  'The administrative geography and civil voting area ontology',
  'CIPA e-Delivery',
], $collection->field_ar_affiliates);
$this->assertReferences(['eProcurement'], $collection->field_policy_domain);
$this->assertEquals(1, $collection->field_ar_elibrary_creation->value);
$this->assertReferences([
  'Dark Side of The Force',
], $collection->get('field_ar_owner'));
$this->assertReferences([
  'DIGIT-CIPA-SUPPORT@ec.europa.eu',
], $collection->get('field_ar_contact_information'));
$this->assertEquals(1, $collection->field_ar_moderation->value);
$this->assertEquals(0, $collection->field_ar_closed->value);
$this->assertEquals('Abstract for a new collection', $collection->field_ar_abstract->value);
$this->assertEquals('content_editor', $collection->field_ar_abstract->format);

$collection = $this->loadEntityByLabel('rdf_entity', 'More than one Community or Repository');
$this->assertEquals('More than one Community or Repository', $collection->label());
$this->assertEquals('collection', $collection->bundle());
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1413718284), $collection->field_ar_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1413718284), $collection->field_ar_modification_date->value);
$this->assertEquals('default', $collection->graph->value);
$this->assertTrue($collection->get('field_ar_affiliates')->isEmpty());
$this->assertReferences([
  'Italy',
  'European Union',
], $collection->field_spatial_coverage);
$this->assertReferences(['Defence'], $collection->field_policy_domain);
$this->assertEquals(1, $collection->field_ar_elibrary_creation->value);
$this->assertTrue($collection->get('field_ar_owner')->isEmpty());
$this->assertTrue($collection->get('field_ar_contact_information')->isEmpty());
$this->assertEquals(1, $collection->field_ar_moderation->value);
$this->assertEquals(0, $collection->field_ar_closed->value);
$this->assertTrue($collection->get('field_ar_abstract')->isEmpty());
$this->assertEquals("<p>We started a free italian repository and we will present it on October 25th for the Italian GnuLinuxDay.</p>\r\n", $collection->field_ar_description->value);
$this->assertEquals('content_editor', $collection->field_ar_description->format);
$logo = File::load($collection->field_ar_logo->target_id);
$this->assertEquals('public://collection/logo/SputnixLogo.jpg', $logo->getFileUri());
$this->assertFileExists('public://collection/logo/SputnixLogo.jpg');
$this->assertEquals(8069, filesize('public://collection/logo/SputnixLogo.jpg'));
$this->assertEquals('http://www.sputnix.it/jit/index.php?option=com_content&view=article&id=138:repository', $collection->field_ar_access_url->uri);

$collection = $this->loadEntityByLabel('rdf_entity', 'Collection from Repository');
$this->assertEquals('Collection from Repository', $collection->label());
$this->assertEquals('collection', $collection->bundle());
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1357303053), $collection->field_ar_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1432113701), $collection->field_ar_modification_date->value);
$this->assertEquals('default', $collection->graph->value);
$this->assertTrue($collection->get('field_ar_affiliates')->isEmpty());
$this->assertReferences(['Finland'], $collection->field_spatial_coverage);
$this->assertReferences(['Open government'], $collection->field_policy_domain);
$this->assertEquals(1, $collection->field_ar_elibrary_creation->value);
$this->assertTrue($collection->get('field_ar_owner')->isEmpty());
$this->assertTrue($collection->get('field_ar_contact_information')->isEmpty());
$this->assertEquals(1, $collection->field_ar_moderation->value);
$this->assertEquals(0, $collection->field_ar_closed->value);
$this->assertTrue($collection->get('field_ar_abstract')->isEmpty());
$this->assertStringEndsWith("standards and different publications</span></p>\r\n", $collection->field_ar_description->value);
$this->assertEquals('content_editor', $collection->field_ar_description->format);
$logo = File::load($collection->field_ar_logo->target_id);
$this->assertEquals('public://collection/logo/yhteentoimivuus_logo.PNG', $logo->getFileUri());
$this->assertFileExists('public://collection/logo/yhteentoimivuus_logo.PNG');
$this->assertEquals(10246, filesize('public://collection/logo/yhteentoimivuus_logo.PNG'));
$this->assertEquals('https://www.avoindata.fi/', $collection->field_ar_access_url->uri);

$collection = $this->loadEntityByLabel('rdf_entity', 'Collection from Community');
$this->assertEquals('Collection from Community', $collection->label());
$this->assertEquals('collection', $collection->bundle());
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1454595297), $collection->field_ar_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1454595297), $collection->field_ar_modification_date->value);
$this->assertEquals('default', $collection->graph->value);
$this->assertTrue($collection->get('field_ar_affiliates')->isEmpty());
$this->assertReferences(static::$europeCountries, $collection->field_spatial_coverage);
$this->assertReferences(['Collaboration'], $collection->field_policy_domain);
$this->assertEquals(0, $collection->field_ar_elibrary_creation->value);
$this->assertTrue($collection->get('field_ar_owner')->isEmpty());
$this->assertTrue($collection->get('field_ar_contact_information')->isEmpty());
$this->assertEquals(1, $collection->field_ar_moderation->value);
$this->assertEquals(0, $collection->field_ar_closed->value);
$this->assertStringEndsWith('This group will offer Open Data stakeholders a possibility to find out more about the latest developments around the European Data Portal. ', $collection->field_ar_abstract->value);
$this->assertEquals('content_editor', $collection->field_ar_abstract->format);
$this->assertStringEndsWith("Discover the portal <a href=\"http://www.europeandataportal.eu\">www.europeandataportal.eu</a></p>\r\n", $collection->field_ar_description->value);
$this->assertEquals('content_editor', $collection->field_ar_description->format);
$logo = File::load($collection->field_ar_logo->target_id);
$this->assertEquals('public://collection/logo/epdp_final_logo1-01.png', $logo->getFileUri());
$this->assertFileExists('public://collection/logo/epdp_final_logo1-01.png');
$this->assertEquals(46068, filesize('public://collection/logo/epdp_final_logo1-01.png'));
$this->assertTrue($collection->get('field_ar_access_url')->isEmpty());

$collection = $this->loadEntityByLabel('rdf_entity', 'Archived collection');
$this->assertEquals('Archived collection', $collection->label());
$this->assertEquals('collection', $collection->bundle());
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1435974264), $collection->field_ar_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1435974264), $collection->field_ar_modification_date->value);
$this->assertEquals('default', $collection->graph->value);
$this->assertReferences([
  'Styles Layer Descriptor',
  'KASPeR - Mapping application of statistical data e-dimensions',
  'Core Location Vocabulary',
  'DCAT application profile for data portals in Europe',
], $collection->field_ar_affiliates);
$this->assertReferences(['Thailand'], $collection->field_spatial_coverage);
$this->assertReferences(['Open government'], $collection->field_policy_domain);
$this->assertEquals(1, $collection->field_ar_elibrary_creation->value);
$this->assertReferences([
  'Geodetic Institute of Slovenia',
], $collection->get('field_ar_owner'));
$this->assertReferences([
  'Geodetic Institute of Slovenia',
], $collection->get('field_ar_contact_information'));
$this->assertEquals(1, $collection->field_ar_moderation->value);
$this->assertEquals(0, $collection->field_ar_closed->value);
$this->assertEquals('ashi', $collection->field_ar_abstract->value);
$this->assertEquals('content_editor', $collection->field_ar_abstract->format);
$this->assertStringEndsWith("โตเกียว แนะนำในแต่ละแห่งว่ามีที่ไหนน่าสนใจบ้าง</p>\r\n", $collection->field_ar_description->value);
$this->assertEquals('content_editor', $collection->field_ar_description->format);
$logo = File::load($collection->field_ar_logo->target_id);
$this->assertEquals('public://collection/logo/girls_fans_33_resize.jpg', $logo->getFileUri());
$this->assertFileExists('public://collection/logo/girls_fans_33_resize.jpg');
$this->assertEquals(55543, filesize('public://collection/logo/girls_fans_33_resize.jpg'));
$this->assertTrue($collection->get('field_ar_access_url')->isEmpty());
