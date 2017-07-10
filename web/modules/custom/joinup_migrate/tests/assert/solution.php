<?php

/**
 * @file
 * Assertions for 'solution' migration.
 */

use Drupal\file\Entity\File;
use Drupal\rdf_entity\Entity\Rdf;

// Imported content check.
/* @var \Drupal\rdf_entity\RdfInterface $solution */
$solution = $this->loadEntityByLabel('rdf_entity', 'The administrative geography and civil voting area ontology', 'solution');
$this->assertEquals('The administrative geography and civil voting area ontology', $solution->label());
$this->assertEquals('solution', $solution->bundle());
$this->assertEquals('default', $solution->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1270080000), $solution->field_is_creation_date->value);
$this->assertReferences([
  'The administrative geography and civil voting area ontology',
], $solution->field_is_distribution);
$this->assertReferences(['eProcurement'], $solution->field_policy_domain);
$this->assertEquals("<p>An ontology describing the administrative and voting area geography of Great Britain</p>\r\n", $solution->field_is_description->value);
$this->assertEquals('content_editor', $solution->field_is_description->format);
$this->assertEquals(1, $solution->field_is_elibrary_creation->value);
$this->assertReferences(['Ordnance Survey'], $solution->get('field_is_owner'));
$this->assertTrue($solution->get('field_is_contact_information')->isEmpty());
$this->assertReferences(['Completed'], $solution->get('field_status'));
$this->assertEquals('validated', $solution->field_is_state->value);
$this->assertRedirects([
  'node/58694',
  'catalogue/asset_release/administrative-geography-and-civil-voting-area-ontology',
], $solution);

$solution = $this->loadEntityByLabel('rdf_entity', 'CIPA e-Delivery', 'solution');
$this->assertEquals('CIPA e-Delivery', $solution->label());
$this->assertEquals('solution', $solution->bundle());
$this->assertEquals('draft', $solution->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1341505914), $solution->field_is_creation_date->value);
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
$this->assertContains('eSENS eDelivery', $solution->field_is_description->value);
$this->assertEquals('content_editor', $solution->field_is_description->format);
$this->assertEquals(1, $solution->field_is_elibrary_creation->value);
$this->assertReferences([
  'Dark Side of The Force',
], $solution->get('field_is_owner'));
$this->assertReferences([
  'DIGIT-CIPA-SUPPORT@ec.europa.eu',
], $solution->get('field_is_contact_information'));
$logo = File::load($solution->field_is_logo->target_id);
$this->assertEquals('public://solution/logo/CIPA_e-Delivery_70x70.png', $logo->getFileUri());
$this->assertFileExists('public://solution/logo/CIPA_e-Delivery_70x70.png');
$this->assertTrue($solution->get('field_status')->isEmpty());
$this->assertEquals('proposed', $solution->field_is_state->value);
$this->assertRedirects([
  'node/49860',
  'software/cipaedelivery/description',
], $solution);

$solution = $this->loadEntityByLabel('rdf_entity', 'Styles Layer Descriptor', 'solution');
$this->assertEquals('Styles Layer Descriptor', $solution->label());
$this->assertEquals('solution', $solution->bundle());
$this->assertEquals('default', $solution->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1393346353), $solution->field_is_creation_date->value);
$this->assertTrue($solution->get('field_is_has_version')->isEmpty());
$this->assertReferences(['Styles Layer Descriptor'], $solution->field_is_distribution);
$this->assertReferences(['Open government'], $solution->field_policy_domain);
$this->assertContains('user-defined symbols and colors to be used in geographic information.', $solution->field_is_description->value);
$this->assertEquals('content_editor', $solution->field_is_description->format);
$this->assertEquals(1, $solution->field_is_elibrary_creation->value);
$this->assertReferences([
  'Forumstandaardisatie.nl',
], $solution->get('field_is_owner'));
$this->assertTrue($solution->get('field_is_contact_information')->isEmpty());
$this->assertReferences(['Completed'], $solution->get('field_status'));
$this->assertEquals('validated', $solution->field_is_state->value);
$this->assertRedirects([
  'node/76726',
  'catalogue/asset_release/styles-layer-descriptor',
], $solution);

$solution = $this->loadEntityByLabel('rdf_entity', 'KASPeR - Mapping application of statistical data e-dimensions', 'solution');
$this->assertEquals('KASPeR - Mapping application of statistical data e-dimensions', $solution->label());
$this->assertEquals('solution', $solution->bundle());
$this->assertEquals('default', $solution->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1419007124), $solution->field_is_creation_date->value);
$this->assertTrue($solution->get('field_is_has_version')->isEmpty());
$this->assertReferences(['KASPeR - Mapping application of statistical data e-dimensions'], $solution->field_is_distribution);
$this->assertReferences(['Open government'], $solution->field_policy_domain);
$this->assertContains('The KASPeR application enables downloading of images and selected spatial layers with the data in vector (*. shp) format.', $solution->field_is_description->value);
$this->assertEquals('content_editor', $solution->field_is_description->format);
$this->assertEquals(1, $solution->field_is_elibrary_creation->value);
$this->assertReferences([
  'Geodetic Institute of Slovenia',
], $solution->get('field_is_owner'));
$this->assertReferences([
  'Geodetic Institute of Slovenia',
], $solution->field_is_contact_information);
$this->assertReferences(['Completed'], $solution->get('field_status'));
$this->assertEquals('validated', $solution->field_is_state->value);
$this->assertRedirects([
  'node/102713',
  'catalogue/asset_release/kasper-mapping-application-statistical-data-e-dimensions',
], $solution);
$translation = $solution->getTranslation('ro');
$this->assertEquals('RO variant for name', $translation->label());
$translation = $solution->getTranslation('et');
$this->assertContains('Lastiliigi liigid on kooskõlas', $translation->field_is_description->value);

$solution = $this->loadEntityByLabel('rdf_entity', 'Core Location Vocabulary', 'solution');
$this->assertEquals('Core Location Vocabulary', $solution->label());
$this->assertEquals('solution', $solution->bundle());
$this->assertEquals('default', $solution->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1329465556), $solution->field_is_creation_date->value);
$this->assertReferences([
  'Core Location Vocabulary 0.2',
  'Core Location Vocabulary 0.3',
  'Core Location Vocabulary 1.00',
], $solution->field_is_has_version);
$this->assertTrue($solution->get('field_is_distribution')->isEmpty());
$this->assertReferences(['Open government'], $solution->field_policy_domain);
$this->assertReferences([
  'digit-semic-team@ec.europa.eu',
], $solution->get('field_is_contact_information'));
$this->assertContains('Virtual Meeting 2012.04.03', $solution->field_is_description->value);
$this->assertEquals('content_editor', $solution->field_is_description->format);
$this->assertEquals(1, $solution->field_is_elibrary_creation->value);
$this->assertReferences(['ACME University'], $solution->get('field_is_owner'));
$this->assertTrue($solution->get('field_status')->isEmpty());
$this->assertEquals('validated', $solution->field_is_state->value);
$this->assertRedirects([
  'node/42444',
  'asset/core_location/description',
], $solution);

$solution = $this->loadEntityByLabel('rdf_entity', 'DCAT application profile for data portals in Europe', 'solution');
$this->assertEquals('DCAT application profile for data portals in Europe', $solution->label());
$this->assertEquals('solution', $solution->bundle());
$this->assertEquals('default', $solution->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1445872685), $solution->field_is_creation_date->value);
$this->assertTrue($solution->get('field_is_distribution')->isEmpty());
$this->assertReferences([
  'DCAT Application Profile for Data Portals in Europe - Draft 1',
  'DCAT Application Profile for Data Portals in Europe - Draft 2',
  'DCAT Application Profile for Data Portals in Europe - Draft 3',
  'DCAT Application Profile for Data Portals in Europe - Final Draft',
  'DCAT Application Profile for Data Portals in Europe - Final',
  'DCAT Application Profile for Data Portals in Europe - Revision',
  'GeoDCAT-AP working drafts',
  'DCAT-AP v1.1',
  'GeoDCAT-AP v1.0',
  'GeoDCAT-AP v1.0.1',
], $solution->get('field_is_has_version'));
$this->assertReferences(['Open government'], $solution->field_policy_domain);
$this->assertTrue($solution->get('field_is_contact_information')->isEmpty());
$this->assertContains('Open Data Support Community', $solution->field_is_description->value);
$this->assertEquals('content_editor', $solution->field_is_description->format);
$this->assertEquals(1, $solution->field_is_elibrary_creation->value);
$this->assertReferences([
  'Dark Side of The Force',
], $solution->get('field_is_owner'));
$this->assertReferences(['Under development'], $solution->get('field_status'));
$this->assertEquals('validated', $solution->field_is_state->value);
$this->assertRedirects([
  'node/63567',
  'asset/dcat_application_profile/description',
], $solution);

$solution = $this->loadEntityByLabel('rdf_entity', 'Asset Description Metadata Schema (ADMS)', 'solution');
$this->assertEquals('Asset Description Metadata Schema (ADMS)', $solution->label());
$this->assertEquals('solution', $solution->bundle());
$this->assertEquals('default', $solution->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1323340905), $solution->field_is_creation_date->value);
$this->assertTrue($solution->get('field_is_distribution')->isEmpty());
$this->assertReferences([
  'ADMS 0.6',
  'ADMS 0.7',
  'ADMS 0.8',
  'ADMS 0.9',
  'ADMS 0.98',
  'ADMS',
  'ADMS Application Profile for Joinup',
  'ADMS-AP for Joinup version 2.0',
  'GHGghg',
], $solution->get('field_is_has_version'));$this->assertReferences(['Open government'], $solution->field_policy_domain);
$this->assertReferences(['contact@semic.eu'], $solution->get('field_is_contact_information'));
$this->assertContains('Towards Open Government Metadata', $solution->field_is_description->value);
$this->assertEquals('content_editor', $solution->field_is_description->format);
$this->assertEquals(1, $solution->field_is_elibrary_creation->value);
$this->assertReferences([
  'Dark Side of The Force',
], $solution->get('field_is_owner'));
$this->assertReferences(['Under development'], $solution->get('field_status'));
$this->assertEquals('validated', $solution->field_is_state->value);
$this->assertRedirects([
  'node/42438',
  'asset/adms/description',
], $solution);

// There are 2 solutions with the same title, so we cannot load by title. We are
// interested in inspecting the solution migrated from node 59180. So, we'll use
// the known URL to load th eentity.
$solution = Rdf::load('http://www.eurofiling.info/corepTaxonomy/taxonomy.shtml#1.4.0');
$this->assertEquals('Common Reporting Framework XBRL Project', $solution->label());
$this->assertEquals('solution', $solution->bundle());
$this->assertEquals('default', $solution->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1320274800), $solution->field_is_creation_date->value);
$this->assertReferences(['1.4.1.corep.zip'], $solution->field_is_distribution);
$this->assertTrue($solution->get('field_is_has_version')->isEmpty());
$this->assertReferences(['Open government'], $solution->field_policy_domain);
$this->assertReferences(['Ignacio Boixo'], $solution->get('field_is_contact_information'));
$this->assertContains('XBRL+Taxonomy+v2.0.0.pdf', $solution->field_is_description->value);
$this->assertEquals('content_editor', $solution->field_is_description->format);
$this->assertEquals(1, $solution->field_is_elibrary_creation->value);
$this->assertReferences([
  'European Banking Authority',
], $solution->get('field_is_owner'));
$this->assertReferences([
  'Evaluation and Report Language (EARL) 1.0 Schema',
], $solution->get('field_is_related_solutions'));
$this->assertEquals('http://www.eurofiling.info/corepTaxonomy/taxonomy.shtml#1.3.1', $solution->get('field_is_translation')->target_id);
$this->assertReferences(['Completed'], $solution->get('field_status'));
$this->assertEquals('validated', $solution->field_is_state->value);
$this->assertRedirects([
  'node/59180',
  'catalogue/asset_release/common-reporting-framework-xbrl-project-0',
], $solution);

$solution = Rdf::load('http://www.eurofiling.info/corepTaxonomy/taxonomy.shtml#1.3.1');
$this->assertEquals('Common Reporting Framework XBRL Project', $solution->label());
$this->assertEquals('solution', $solution->bundle());
$this->assertEquals('default', $solution->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1262732400), $solution->field_is_creation_date->value);
$this->assertReferences(['1.3.1.core.zip'], $solution->field_is_distribution);
$this->assertTrue($solution->get('field_is_has_version')->isEmpty());
$this->assertReferences(['Open government'], $solution->field_policy_domain);
$this->assertReferences(['Romain Loth'], $solution->get('field_is_contact_information'));
$this->assertContains('EU capital requirements regime.', $solution->field_is_description->value);
$this->assertEquals('content_editor', $solution->field_is_description->format);
$this->assertEquals(1, $solution->field_is_elibrary_creation->value);
$this->assertReferences([
  'European Banking Authority',
], $solution->get('field_is_owner'));
$this->assertReferences(['Completed'], $solution->get('field_status'));
$this->assertEquals('validated', $solution->field_is_state->value);
$this->assertRedirects([
  'node/59183',
  'catalogue/asset_release/common-reporting-framework-xbrl-project-1',
], $solution);

$solution = Rdf::load('http://www.w3.org/TR/2011/WD-EARL10-Schema-20110510');
$this->assertEquals('Evaluation and Report Language (EARL) 1.0 Schema', $solution->label());
$this->assertEquals('solution', $solution->bundle());
$this->assertEquals('default', $solution->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1304985600), $solution->field_is_creation_date->value);
$this->assertReferences([
  'Evaluation and Report Language (EARL) 1.0 Schema',
], $solution->field_is_distribution);
$this->assertTrue($solution->get('field_is_has_version')->isEmpty());
$this->assertReferences(['Open government'], $solution->field_policy_domain);
$this->assertTrue($solution->get('field_is_contact_information')->isEmpty());
$this->assertContains('validation purposes.', $solution->field_is_description->value);
$this->assertEquals('content_editor', $solution->field_is_description->format);
$this->assertEquals(1, $solution->field_is_elibrary_creation->value);
$this->assertReferences([
  'World Wide Web Consortium',
], $solution->get('field_is_owner'));
$this->assertReferences(['Under development'], $solution->get('field_status'));
$this->assertEquals('validated', $solution->field_is_state->value);
$this->assertRedirects([
  'node/60208',
  'catalogue/asset_release/evaluation-and-report-language-earl-10-schema',
], $solution);

$solution = $this->loadEntityByLabel('rdf_entity', 'Digital Signature Service', 'solution');
$this->assertEquals('Digital Signature Service', $solution->label());
$this->assertEquals('solution', $solution->bundle());
$this->assertEquals('default', $solution->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1312882209), $solution->field_is_creation_date->value);
$this->assertTrue($solution->get('field_is_distribution')->isEmpty());
$this->assertReferences([
  'sd-dss 1.00',
  'sd-dss 1.02',
  'sd-dss 2.00',
  'sd-dss 2.0.1',
  'sd-dss 2.0.2',
  'sd-dss 3.0.2',
  'sd-dss 3.0.3',
  'sd-dss 4.0.2',
  'sd-dss ',
  'SD-DSS 4.1.0 RC',
  'SD-DSS ',
  'SD-DSS 4.2.0-RC',
  'SD-DSS 4.2.0',
  'SD-DSS 4.3.0-RC',
  'DSS 4.3.0',
  'DSS 4.4.RC1',
  'DSS 4.4.RC2',
  'DSS 4.4.0',
  'DSS 4.5.RC1',
  'DSS 4.5.RC2',
  'DSS 4.5.0',
  'DSS 4.6.RC1',
  'DSS 4.6.RC2',
  'DSS 4.6.0',
  'DSS 4.7.RC1',
  'DSS 4.7.RC2',
  'DSS 4.7.0',
], $solution->get('field_is_has_version'));
$this->assertReferences(['Open government'], $solution->field_policy_domain);
$this->assertReferences(['david.naramski@nowina.lu'], $solution->get('field_is_contact_information'));
$this->assertContains('release note and support, can be found at:', $solution->field_is_description->value);
$this->assertEquals('content_editor', $solution->field_is_description->format);
$this->assertEquals(1, $solution->field_is_elibrary_creation->value);
$this->assertTrue($solution->get('field_is_owner')->isEmpty());
$this->assertTrue($solution->get('field_status')->isEmpty());
$this->assertEquals('validated', $solution->field_is_state->value);
$this->assertRedirects([
  'node/27024',
  'asset/sd-dss/description',
], $solution);
