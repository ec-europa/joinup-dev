<?php

/**
 * @file
 * Assertions for 'contact' migration.
 */

// Migration counts.
$this->assertTotalCount('contact', 1);
$this->assertSuccessCount('contact', 1);

// Imported content check.
/* @var \Drupal\rdf_entity\RdfInterface $contact */
$contact = $this->loadEntityByLabel('rdf_entity', 'Geodetic Institute of Slovenia', 'contact_information');
$this->assertEquals('Geodetic Institute of Slovenia', $contact->label());
$this->assertEquals('contact_information', $contact->bundle());
$this->assertEquals('default', $contact->graph->value);
$this->assertEquals('Geodetic Institute of Slovenia', $contact->field_ci_name->value);
$this->assertEquals('info@gis.si', $contact->field_ci_email->value);
$this->assertEquals('http://www.gis.si/en', $contact->field_ci_webpage->uri);
$this->assertEquals('validated', $contact->field_ci_state->value);
