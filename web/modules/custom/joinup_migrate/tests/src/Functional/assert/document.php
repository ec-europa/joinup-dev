<?php

/**
 * @file
 * Assertions for 'document' migration.
 */

use Drupal\file_url\FileUrlHandler;

// Migration counts.
$this->assertTotalCount('document', 7);
$this->assertSuccessCount('document', 7);

// Imported content check.
/* @var \Drupal\node\NodeInterface $document */
$document = $this->loadEntityByLabel('node', 'BAA');
$this->assertEquals('BAA', $document->label());
$this->assertEquals('document', $document->bundle());
$this->assertEquals('document', $document->field_type->value);
$this->assertEquals(1287568701, $document->created->value);
$this->assertEquals(1426669226, $document->changed->value);
$this->assertEquals(1, $document->uid->target_id);
$this->assertEquals('2010-10-20T00:00:00', $document->field_document_publication_date->value);
$this->assertEquals('http://www.baa.com/', $document->field_file->target_id);
$this->assertStringEndsWith("<p>More information can be found on the <a href=\"http://www.baa.com/\">website</a>.</p>\r\n<p>Nature of documentation: Other</p>", $document->body->value);
$this->assertTrue($document->get('field_keywords')->isEmpty());
$this->assertReferences(static::$europeCountries, $document->field_document_spatial_coverage);
$this->assertEquals($new_collection->id(), $document->og_audience->target_id);
$this->assertEquals('validated', $document->field_state->value);
// @todo Test also the licence when the final excel mapping table is in.

$document = $this->loadEntityByLabel('node', 'Good Practice Study');
$this->assertEquals('Good Practice Study', $document->label());
$this->assertEquals('document', $document->bundle());
$this->assertEquals('document', $document->field_type->value);
$this->assertEquals(1214179200, $document->created->value);
$this->assertEquals(1323887628, $document->changed->value);
$this->assertEquals(1, $document->uid->target_id);
$this->assertEquals('2008-06-23T00:00:00', $document->field_document_publication_date->value);
$file = FileUrlHandler::urlToFile($document->field_file->target_id);
$this->assertEquals('public://document/2011-12/good-practice-study.pdf', $file->getFileUri());
$this->assertStringEndsWith("interoperability and exchange of solutions.</div>\r\n<p>Nature of documentation: Guide</p>", $document->body->value);
$this->assertTrue($document->get('field_keywords')->isEmpty());
$this->assertReferences(['Belgium'], $document->field_document_spatial_coverage);
$this->assertEquals($new_collection->id(), $document->og_audience->target_id);
$this->assertEquals('validated', $document->field_state->value);
// @todo Test also the licence when the final excel mapping table is in.

$document = $this->loadEntityByLabel('node', 'CAMSS method (v1.0) scenario 2 - SMEF');
$this->assertEquals('CAMSS method (v1.0) scenario 2 - SMEF', $document->label());
$this->assertEquals('document', $document->bundle());
$this->assertEquals('document', $document->field_type->value);
$this->assertEquals(1425914198, $document->created->value);
$this->assertEquals(1425914198, $document->changed->value);
$this->assertEquals(1, $document->uid->target_id);
$this->assertEquals('2015-03-09T00:00:00', $document->field_document_publication_date->value);
$file = FileUrlHandler::urlToFile($document->field_file->target_id);
$this->assertEquals('public://document/2015-03/CAMSS method (v1.0) scenario 2 - SMEF.xlsm', $file->getFileUri());
$this->assertStringEndsWith("v1.0 by the CAMSS team.</div>\r\n<p>Nature of documentation: CAMSS Assessment</p>", $document->body->value);
$this->assertKeywords(['CAMSS', 'Netherlands', 'SMEF', 'standard'], $document);
$this->assertReferences(static::$europeCountries, $document->field_document_spatial_coverage);
$this->assertEquals($new_collection->id(), $document->og_audience->target_id);
$this->assertEquals('proposed', $document->field_state->value);
// @todo Test also the licence when the final excel mapping table is in.

$document = $this->loadEntityByLabel('node', 'The Irish ePassport');
$this->assertEquals('The Irish ePassport', $document->label());
$this->assertEquals('document', $document->bundle());
$this->assertEquals('case', $document->field_type->value);
$this->assertEquals(1170370800, $document->created->value);
$this->assertEquals(1170751289, $document->changed->value);
$this->assertEquals(1, $document->uid->target_id);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1170370800), $document->field_document_publication_date->value);
$this->assertEquals('http://www.dfa.ie/home/index.aspx?id=265', $document->field_file->target_id);
$this->assertStringEndsWith("ensure that the documents can be read by border control agencies.", $document->body->value);
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
// @todo Test also the licence when the final excel mapping table is in.

$document = $this->loadEntityByLabel('node', 'National Interoperability Framework Observatory (NIFO) Factsheets');
$this->assertEquals('National Interoperability Framework Observatory (NIFO) Factsheets', $document->label());
$this->assertEquals('document', $document->bundle());
$this->assertEquals('factsheet', $document->field_type->value);
$this->assertEquals(1353062565, $document->created->value);
$this->assertEquals(1453821476, $document->changed->value);
$this->assertEquals(1, $document->uid->target_id);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1353062565), $document->field_document_publication_date->value);
$file = FileUrlHandler::urlToFile($document->field_file->target_id);
$this->assertEquals('public://document/2013-02/NIFO - Factsheet Austria 02-2013.pdf', $file->getFileUri());
$this->assertStringEndsWith("interoperability in each of the Countries in scope.&nbsp;</div>\r\n</div>\r\n<p>&nbsp;</p>", $document->body->value);
$this->assertKeywords([
  'Country profile',
  'Government Interoperability Frameworks',
  'nifo',
], $document);
$this->assertReferences(static::$europeCountries, $document->field_document_spatial_coverage);
$this->assertEquals($new_collection->id(), $document->og_audience->target_id);
$this->assertEquals('validated', $document->field_state->value);
// @todo Test also the licence when the final excel mapping table is in.

$document = $this->loadEntityByLabel('node', 'ISA Contributor Agreement v1.1');
$this->assertEquals('ISA Contributor Agreement v1.1', $document->label());
$this->assertEquals('document', $document->bundle());
$this->assertEquals('legal', $document->field_type->value);
$this->assertEquals(1362756715, $document->created->value);
$this->assertEquals(1362756715, $document->changed->value);
$this->assertEquals(1, $document->uid->target_id);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1362756715), $document->field_document_publication_date->value);
$this->assertStringEndsWith(", will be considered as a valid commitment.</p>\r\n\t\t\t</div>\r\n\t\t</div>\r\n\t</div>\r\n</div>\r\n<p>&nbsp;</p>", $document->body->value);
$this->assertTrue($document->get('field_keywords')->isEmpty());
$this->assertTrue($document->get('field_document_spatial_coverage')->isEmpty());
$this->assertEquals($new_collection->id(), $document->og_audience->target_id);
$this->assertEquals('validated', $document->field_state->value);
// @todo Test also the licence when the final excel mapping table is in.

$document = $this->loadEntityByLabel('node', 'E-government Action plan 2016-2020 - Opinion of the European Committee of the Regions - Martin Andreasson');
$this->assertEquals('E-government Action plan 2016-2020 - Opinion of the European Committee of the Regions - Martin Andreasson', $document->label());
$this->assertEquals('document', $document->bundle());
$this->assertEquals('presentation', $document->field_type->value);
$this->assertEquals(1474971682, $document->created->value);
$this->assertEquals(1474971682, $document->changed->value);
$this->assertGreaterThan(1, $document->uid->target_id);
$this->assertEquals('2016-09-27T00:00:00', $document->field_document_publication_date->value);
$file = FileUrlHandler::urlToFile($document->field_file->target_id);
$this->assertEquals('public://document/2016-09/e-government_action_plan_2016-2020_-_opinion_of_the_european_committee_of_the_regions_-_martin_andreasson.pdf', $file->getFileUri());
$this->assertStringEndsWith('took place on 20 September 2016 in Brussels.</span></p>', $document->body->value);
$this->assertKeywords(['Other'], $document);
$this->assertReferences(static::$europeCountries, $document->field_document_spatial_coverage);
$this->assertEquals($new_collection->id(), $document->og_audience->target_id);
$this->assertEquals('proposed', $document->field_state->value);
// @todo Test also the licence when the final excel mapping table is in.

$document = $this->loadEntityByLabel('node', "FR: 6th Edition of 'Words of Elected Representatives'");
$this->assertEquals("FR: 6th Edition of 'Words of Elected Representatives'", $document->label());
$this->assertEquals('document', $document->bundle());
$this->assertEquals('document', $document->field_type->value);
$this->assertEquals(1291966861, $document->created->value);
$this->assertEquals(1292245784, $document->changed->value);
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
$this->assertStringEndsWith("attentions to the citizens.</p><p><b>Number of pages</b>: 99</p><p>Nature of documentation: Book</p>", $document->body->value);
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
// @todo Test also the licence when the final excel mapping table is in.
