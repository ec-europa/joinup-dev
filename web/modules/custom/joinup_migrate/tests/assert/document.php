<?php

/**
 * @file
 * Assertions for 'document' migration.
 */

use Drupal\file_url\FileUrlHandler;
use Drupal\node\Entity\Node;

// Imported content check.
$document = Node::load(139528);
$this->assertEquals('BAA', $document->label());
$this->assertEquals('document', $document->bundle());
$this->assertEquals('document', $document->field_type->value);
$this->assertEquals(1287568701, $document->created->value);
$this->assertEquals(1, $document->uid->target_id);
$this->assertEquals('2010-10-20T00:00:00', $document->field_document_publication_date->value);
$this->assertEquals('http://www.baa.com/', $document->field_file->target_id);
$this->assertContains('More information can be found on the ', $document->body->value);
$this->assertTrue($document->get('field_keywords')->isEmpty());
$this->assertTrue($document->get('field_document_spatial_coverage')->isEmpty());
$this->assertEquals($new_collection->id(), $document->og_audience->target_id);
$this->assertEquals('validated', $document->field_state->value);
$this->assertRedirects(['elibrary/document/baa'], $document);
// @todo Test also the licence when the final excel mapping table is in.

$document = Node::load(42233);
$this->assertEquals('Good Practice Study', $document->label());
$this->assertEquals('document', $document->bundle());
$this->assertEquals('document', $document->field_type->value);
$this->assertEquals(1214179200, $document->created->value);
$this->assertEquals(1, $document->uid->target_id);
$this->assertEquals('2008-06-23T00:00:00', $document->field_document_publication_date->value);
$file = FileUrlHandler::urlToFile($document->field_file->target_id);
$this->assertEquals('public://document/2011-12/good-practice-study.pdf', $file->getFileUri());
$this->assertContains('interoperability and exchange of solutions.', $document->body->value);
$this->assertTrue($document->get('field_keywords')->isEmpty());
$this->assertReferences(['Belgium'], $document->field_document_spatial_coverage);
$this->assertEquals($new_collection->id(), $document->og_audience->target_id);
$this->assertEquals('validated', $document->field_state->value);
$this->assertRedirects(['elibrary/document/good-practice-study'], $document);
// @todo Test also the licence when the final excel mapping table is in.

$document = Node::load(138766);
$this->assertEquals('CAMSS method (v1.0) scenario 2 - SMEF', $document->label());
$this->assertEquals('document', $document->bundle());
$this->assertEquals('document', $document->field_type->value);
$this->assertEquals(1425914198, $document->created->value);
$this->assertEquals(1, $document->uid->target_id);
$this->assertEquals('2015-03-09T00:00:00', $document->field_document_publication_date->value);
$file = FileUrlHandler::urlToFile($document->field_file->target_id);
$this->assertEquals('public://document/2015-03/CAMSS method (v1.0) scenario 2 - SMEF.xlsm', $file->getFileUri());
$this->assertContains('v1.0 by the CAMSS team.', $document->body->value);
$this->assertKeywords(['CAMSS', 'Netherlands', 'SMEF', 'standard'], $document);
$this->assertTrue($document->get('field_document_spatial_coverage')->isEmpty());
$this->assertEquals($new_collection->id(), $document->og_audience->target_id);
$this->assertEquals('proposed', $document->field_state->value);
$this->assertRedirects(['community/camss/document/camss-method-v10-scenario-2-smef'], $document);
// @todo Test also the licence when the final excel mapping table is in.

$document = Node::load(133560);
$this->assertEquals('The Irish ePassport', $document->label());
$this->assertEquals('document', $document->bundle());
$this->assertEquals('case', $document->field_type->value);
$this->assertEquals(1170370800, $document->created->value);
$this->assertEquals(1, $document->uid->target_id);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1170370800), $document->field_document_publication_date->value);
$this->assertEquals('http://www.dfa.ie/home/index.aspx?id=265', $document->field_file->target_id);
$this->assertContains('ensure that the documents can be read by border control agencies.', $document->body->value);
$this->assertKeywords([
  'Administrative',
  'biometric',
  'Citizen',
  'Crime, Justice and Law',
  'Customs',
  'ID',
], $document);
$this->assertReferences(['Ireland'], $document->field_document_spatial_coverage);
$this->assertEquals($new_collection->id(), $document->og_audience->target_id);
$this->assertEquals('validated', $document->field_state->value);
$this->assertRedirects(['community/epractice/case/irish-epassport'], $document);
// @todo Test also the licence when the final excel mapping table is in.

$document = Node::load(53012);
$this->assertEquals('National Interoperability Framework Observatory (NIFO) Factsheets', $document->label());
$this->assertEquals('document', $document->bundle());
$this->assertEquals('factsheet', $document->field_type->value);
$this->assertEquals(1353062565, $document->created->value);
$this->assertEquals(1, $document->uid->target_id);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1353062565), $document->field_document_publication_date->value);
$file = FileUrlHandler::urlToFile($document->field_file->target_id);
$this->assertReferences([
  'NIFO - Factsheet Austria 02-2013.pdf',
  'NIFO - Factsheet Belgium 2011.pdf',
  'NIFO - Factsheet Bulgaria 2011.pdf',
  'NIFO - Factsheet Cyprus 11-2012.pdf',
  'NIFO – Factsheet Denmark 11-2012.pdf',
  'NIFO – Factsheet Estonia 11-2012.pdf',
  'NIFO – Factsheet Finland 01-2013.pdf',
  'NIFO - Factsheet France 2011.pdf',
  'NIFO - Factsheet Germany 05-2013.pdf',
  'NIFO - Factsheet Greece 01-2013.pdf',
  'NIFO - Factsheet Hungary 01-2013.pdf',
  'NIFO - Factsheet Iceland 2011.pdf',
  'NIFO - Factsheet Ireland 2011.pdf',
  'NIFO - Factsheet Italy 05-2013.pdf',
  'NIFO - Factsheet Latvia 12-2012.pdf',
  'NIFO – Factsheet Liechtenstein 11-2012.pdf',
  'NIFO – Factsheet Lithuania 11-2012.pdf',
  'NIFO - Factsheet Luxembourg 01-2013.pdf',
  'NIFO - Factsheet Malta 01-2013.pdf',
  'NIFO - Factsheet Norway 05-2013.pdf',
  'NIFO - Factsheet Poland 05-2013.pdf',
  'NIFO - Factsheet Portugal 01-2013.pdf',
  'NIFO - Factsheet Romania 2011.pdf',
  'NIFO - Factsheet Slovakia 11-2012.pdf',
  'NIFO - Factsheet Slovenia 02-2013.pdf',
  'NIFO - Factsheet Spain 05-2013.pdf',
  'NIFO - Factsheet Sweden 01-2013.pdf',
  'NIFO - Factsheet Switzerland 11-2012.pdf',
  'NIFO - Factsheet The Netherlands 2011.pdf',
  'NIFO – Factsheet United Kingdom 11-2012.pdf',
], $document->get('field_file'));
$this->assertContains('interoperability in each of the Countries in scope.', $document->body->value);
$this->assertKeywords([
  'Country profile',
  'Government Interoperability Frameworks',
  'nifo',
], $document);
$this->assertTrue($document->get('field_document_spatial_coverage')->isEmpty());
$this->assertEquals($new_collection->id(), $document->og_audience->target_id);
$this->assertEquals('validated', $document->field_state->value);
$this->assertRedirects(['elibrary/factsheet/national-interoperability-framework-observatory-nifo-factsheets'], $document);
// @todo Test also the licence when the final excel mapping table is in.

$document = Node::load(63578);
$this->assertEquals('ISA Contributor Agreement v1.1', $document->label());
$this->assertEquals('document', $document->bundle());
$this->assertEquals('legal', $document->field_type->value);
$this->assertEquals(1362756715, $document->created->value);
$this->assertEquals(1, $document->uid->target_id);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1362756715), $document->field_document_publication_date->value);
$this->assertContains(', will be considered as a valid commitment.', $document->body->value);
$this->assertTrue($document->get('field_keywords')->isEmpty());
$this->assertTrue($document->get('field_document_spatial_coverage')->isEmpty());
$this->assertEquals($new_collection->id(), $document->og_audience->target_id);
$this->assertEquals('validated', $document->field_state->value);
$this->assertRedirects(['asset/dcat_application_profile/legaldocument/isa-contributor-agreement-v11'], $document);
// @todo Test also the licence when the final excel mapping table is in.

$document = Node::load(155691);
$this->assertEquals('E-government Action plan 2016-2020 - Opinion of the European Committee of the Regions - Martin Andreasson', $document->label());
$this->assertEquals('document', $document->bundle());
$this->assertEquals('presentation', $document->field_type->value);
$this->assertEquals(1474971682, $document->created->value);
$this->assertGreaterThan(1, $document->uid->target_id);
$this->assertEquals('2016-09-27T00:00:00', $document->field_document_publication_date->value);
$file = FileUrlHandler::urlToFile($document->field_file->target_id);
$this->assertEquals('public://document/2016-09/e-government_action_plan_2016-2020_-_opinion_of_the_european_committee_of_the_regions_-_martin_andreasson.pdf', $file->getFileUri());
$this->assertContains('took place on 20 September 2016 in Brussels.', $document->body->value);
$this->assertKeywords(['Other'], $document);
$this->assertTrue($document->get('field_document_spatial_coverage')->isEmpty());
$this->assertEquals($new_collection->id(), $document->og_audience->target_id);
$this->assertEquals('proposed', $document->field_state->value);
$this->assertRedirects(['elibrary/presentation/e-government-action-plan-2016-2020-opinion-european-committee-regions-martin-a'], $document);
// @todo Test also the licence when the final excel mapping table is in.

$document = Node::load(125548);
$this->assertEquals("FR: 6th Edition of 'Words of Elected Representatives'", $document->label());
$this->assertEquals('document', $document->bundle());
$this->assertEquals('document', $document->field_type->value);
$this->assertEquals(1291966861, $document->created->value);
$this->assertEquals(1, $document->uid->target_id);
$this->assertEquals('2010-11-30T00:00:00', $document->field_document_publication_date->value);
$names = [];
foreach ($document->get('field_file') as $item) {
  $file = FileUrlHandler::urlToFile($item->target_id);
  $names[] = $file->getFilename();
}
sort($names);
$expected = [
  'Liberte, egalite, connectes - Introduction.pdf',
  'Liberte, egalite, connectes - Chapitre Developpement economique_0.pdf',
  'Liberte, egalite, connectes - Chapitre Gestion interne de la collectivite.pdf',
  'Liberte, egalite, connectes - Chapitre Sante et social.pdf',
  'Liberte, egalite, connectes - Chapitre Services au public.pdf',
  'Liberte, egalite, connectes - Chapitre Tourisme et culture.pdf',
  'Liberte, egalite, connectes - Chapitre Education.pdf',
  'tome-6-2010',
];
sort($expected);
$this->assertSame($expected, $names);
$this->assertContains('attentions to the citizens.', $document->body->value);
$this->assertKeywords([
  'ICT for Governance',
  "Paroles d'Ã©lus",
  'Public Services',
  'Words of Elected Representatives',
  'accessibility',
  'culture and education',
  'economic development',
  'health and social care',
  'local authority management',
  'online public services',
  'public communities',
  'tourism',
], $document);
$this->assertReferences(['France'], $document->field_document_spatial_coverage);
$collection = $this->loadEntityByLabel('rdf_entity', 'Archived collection', 'collection');
$this->assertEquals($collection->id(), $document->og_audience->target_id);
$this->assertEquals('validated', $document->field_state->value);
$this->assertRedirects(['community/epractice/document/fr-6th-edition-words-elected-representatives'], $document);
// @todo Test also the licence when the final excel mapping table is in.
