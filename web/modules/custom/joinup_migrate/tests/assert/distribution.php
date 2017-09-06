<?php

/**
 * @file
 * Assertions for 'distribution' migration.
 */

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
