<?php

/**
 * @file
 * Assertions for 'solution' migration.
 */

use Drupal\file\Entity\File;
use Drupal\rdf_entity\Entity\Rdf;

// Migration counts.
$this->assertTotalCount('solution', 9);
$this->assertSuccessCount('solution', 9);

// Imported content check.
/* @var \Drupal\rdf_entity\RdfInterface $solution */
$solution = $this->loadEntityByLabel('rdf_entity', 'The administrative geography and civil voting area ontology', 'solution');
$this->assertEquals('The administrative geography and civil voting area ontology', $solution->label());
$this->assertEquals('solution', $solution->bundle());
$this->assertEquals('default', $solution->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1270080000), $solution->field_is_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1449493447), $solution->field_is_modification_date->value);
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

$solution = $this->loadEntityByLabel('rdf_entity', 'CIPA e-Delivery', 'solution');
$this->assertEquals('CIPA e-Delivery', $solution->label());
$this->assertEquals('solution', $solution->bundle());
$this->assertEquals('draft', $solution->graph->value);
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
$this->assertReferences([
  'Dark Side of The Force',
], $solution->get('field_is_owner'));
$this->assertReferences([
  'DIGIT-CIPA-SUPPORT@ec.europa.eu',
], $solution->get('field_is_contact_information'));
$logo = File::load($solution->field_is_logo->target_id);
$this->assertEquals('public://solution/logo/CIPA_e-Delivery_70x70.png', $logo->getFileUri());
$this->assertFileExists('public://solution/logo/CIPA_e-Delivery_70x70.png');
$this->assertEquals(1435, filesize('public://solution/logo/CIPA_e-Delivery_70x70.png'));
$this->assertTrue($solution->get('field_status')->isEmpty());
$this->assertEquals('proposed', $solution->field_is_state->value);

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
$this->assertReferences([
  'Forumstandaardisatie.nl',
], $solution->get('field_is_owner'));
$this->assertTrue($solution->get('field_is_contact_information')->isEmpty());
$this->assertReferences(['Completed'], $solution->get('field_status'));
$this->assertEquals('validated', $solution->field_is_state->value);

$solution = $this->loadEntityByLabel('rdf_entity', 'KASPeR - Mapping application of statistical data e-dimensions', 'solution');
$this->assertEquals('KASPeR - Mapping application of statistical data e-dimensions', $solution->label());
$this->assertEquals('solution', $solution->bundle());
$this->assertEquals('default', $solution->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1419007124), $solution->field_is_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1423650568), $solution->field_is_modification_date->value);
$this->assertTrue($solution->get('field_is_has_version')->isEmpty());
$this->assertReferences(['KASPeR - Mapping application of statistical data e-dimensions'], $solution->field_is_distribution);
$this->assertReferences(['Open government'], $solution->field_policy_domain);
$this->assertStringEndsWith("The KASPeR application enables downloading of images and selected spatial layers with the data in vector (*. shp) format.</p>\r\n", $solution->field_is_description->value);
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

$solution = $this->loadEntityByLabel('rdf_entity', 'Core Location Vocabulary', 'solution');
$this->assertEquals('Core Location Vocabulary', $solution->label());
$this->assertEquals('solution', $solution->bundle());
$this->assertEquals('default', $solution->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1329465556), $solution->field_is_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1462891660), $solution->field_is_modification_date->value);
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
$this->assertStringEndsWith("Virtual Meeting 2012.04.03</a></li>\r\n</ul>\r\n", $solution->field_is_description->value);
$this->assertEquals('content_editor', $solution->field_is_description->format);
$this->assertEquals(1, $solution->field_is_elibrary_creation->value);
$this->assertReferences(['ACME University'], $solution->get('field_is_owner'));
$this->assertTrue($solution->get('field_status')->isEmpty());
$this->assertEquals('validated', $solution->field_is_state->value);

// There are 2 solutions with the same title, so we cannot load by title. We are
// interested in inspecting the solution migrated from node 59180. So, we'll use
// the known URL to load th eentity.
$solution = Rdf::load('http://www.eurofiling.info/corepTaxonomy/taxonomy.shtml#1.4.0');
$this->assertEquals('Common Reporting Framework XBRL Project', $solution->label());
$this->assertEquals('solution', $solution->bundle());
$this->assertEquals('default', $solution->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1320274800), $solution->field_is_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1449675309), $solution->field_is_modification_date->value);
$this->assertReferences(['1.4.1.corep.zip'], $solution->field_is_distribution);
$this->assertTrue($solution->get('field_is_has_version')->isEmpty());
$this->assertReferences(['Open government'], $solution->field_policy_domain);
$this->assertReferences(['Ignacio Boixo'], $solution->get('field_is_contact_information'));
$this->assertStringEndsWith("XBRL+Taxonomy+v2.0.0.pdf</a></li>\r\n</ul>\r\n", $solution->field_is_description->value);
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

$solution = Rdf::load('http://www.eurofiling.info/corepTaxonomy/taxonomy.shtml#1.3.1');
$this->assertEquals('Common Reporting Framework XBRL Project', $solution->label());
$this->assertEquals('solution', $solution->bundle());
$this->assertEquals('default', $solution->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1262732400), $solution->field_is_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1449675322), $solution->field_is_modification_date->value);
$this->assertReferences(['1.3.1.core.zip'], $solution->field_is_distribution);
$this->assertTrue($solution->get('field_is_has_version')->isEmpty());
$this->assertReferences(['Open government'], $solution->field_policy_domain);
$this->assertReferences(['Romain Loth'], $solution->get('field_is_contact_information'));
$this->assertStringEndsWith("EU capital requirements regime.</p>\r\n", $solution->field_is_description->value);
$this->assertEquals('content_editor', $solution->field_is_description->format);
$this->assertEquals(1, $solution->field_is_elibrary_creation->value);
$this->assertReferences([
  'European Banking Authority',
], $solution->get('field_is_owner'));
$this->assertReferences(['Completed'], $solution->get('field_status'));
$this->assertEquals('validated', $solution->field_is_state->value);

$solution = Rdf::load('http://www.w3.org/TR/2011/WD-EARL10-Schema-20110510');
$this->assertEquals('Evaluation and Report Language (EARL) 1.0 Schema', $solution->label());
$this->assertEquals('solution', $solution->bundle());
$this->assertEquals('default', $solution->graph->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1304985600), $solution->field_is_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1455802561), $solution->field_is_modification_date->value);
$this->assertReferences([
  'Evaluation and Report Language (EARL) 1.0 Schema',
], $solution->field_is_distribution);
$this->assertTrue($solution->get('field_is_has_version')->isEmpty());
$this->assertReferences(['Open government'], $solution->field_policy_domain);
$this->assertTrue($solution->get('field_is_contact_information')->isEmpty());
$this->assertStringEndsWith("assurance and\nvalidation purposes.", $solution->field_is_description->value);
$this->assertEquals('content_editor', $solution->field_is_description->format);
$this->assertEquals(1, $solution->field_is_elibrary_creation->value);
$this->assertReferences([
  'World Wide Web Consortium',
], $solution->get('field_is_owner'));
$this->assertReferences(['Under development'], $solution->get('field_status'));
$this->assertEquals('validated', $solution->field_is_state->value);
