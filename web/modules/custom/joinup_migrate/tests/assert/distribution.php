<?php

/**
 * @file
 * Assertions for 'distribution' migration.
 */

use Drupal\file_url\FileUrlHandler;

/* @var \Drupal\rdf_entity\RdfInterface $distribution */
$distribution = $this->loadEntityByLabel('rdf_entity', 'Release Notes 2.2.4', 'asset_distribution');
$this->assertEquals('Release Notes 2.2.4', $distribution->label());
$this->assertEquals('asset_distribution', $distribution->bundle());
$this->assertEquals('default', $distribution->graph->value);
$this->assertEquals(1426517881, $distribution->getCreatedTime());
$translation = $distribution->getTranslation('cs');
$this->assertEquals('DRUH STÁTNÍ SLUŽBY/DOBA TRVÁNÍ PRACOVNÍHO POMĚRU', $translation->field_ad_description->value);
$this->assertEquals('Číselník - TYP PŘEDPISU PRO VZNIK/ZÁNIK SYSTEMIZOVANÉHO MÍSTA', $translation->label());
$translation = $distribution->getTranslation('hu');
$this->assertEquals('Palinka', $translation->field_ad_description->value);
$this->assertEquals('Fekete Kutya', $translation->label());
$this->assertReferences(['CIPA e-Delivery'], $distribution->og_audience);
$file = FileUrlHandler::urlToFile($distribution->field_ad_access_url->target_id);
$name1 = basename($file->getFileUri());
$this->assertRegExp('/^release_notes_2\.2\.4(_\d)?\.pdf$/', $name1);

$distribution = $this->loadEntityByLabel('rdf_entity', 'Release notes 3.2.0', 'asset_distribution');
$file = FileUrlHandler::urlToFile($distribution->field_ad_access_url->target_id);
$name2 = basename($file->getFileUri());
$this->assertRegExp('/^release_notes_2\.2\.4(_\d)?\.pdf$/', $name2);

// File names should not collide.
$this->assertNotEquals($name1, $name2);
