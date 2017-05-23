<?php

/**
 * @file
 * Assertions for 'contact' and 'contact_email' migration.
 */

// Migration counts.
$this->assertTotalCount('contact', 3);
$this->assertSuccessCount('contact', 3);
$this->assertTotalCount('contact_email', 2);
$this->assertSuccessCount('contact_email', 2);

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

$contact = $this->loadEntityByLabel('rdf_entity', 'DIGIT-CIPA-SUPPORT@ec.europa.eu', 'contact_information');
$this->assertEquals('DIGIT-CIPA-SUPPORT@ec.europa.eu', $contact->label());
$this->assertEquals('contact_information', $contact->bundle());
$this->assertEquals('default', $contact->graph->value);
$this->assertEquals('DIGIT-CIPA-SUPPORT@ec.europa.eu', $contact->field_ci_name->value);
$this->assertEquals('DIGIT-CIPA-SUPPORT@ec.europa.eu', $contact->field_ci_email->value);
$this->assertTrue($contact->get('field_ci_webpage')->isEmpty());
$this->assertEquals('validated', $contact->field_ci_state->value);

$contact = $this->loadEntityByLabel('rdf_entity', 'digit-semic-team@ec.europa.eu', 'contact_information');
$this->assertEquals('digit-semic-team@ec.europa.eu', $contact->label());
$this->assertEquals('contact_information', $contact->bundle());
$this->assertEquals('default', $contact->graph->value);
$this->assertEquals('digit-semic-team@ec.europa.eu', $contact->field_ci_name->value);
$this->assertEquals('digit-semic-team@ec.europa.eu', $contact->field_ci_email->value);
$this->assertTrue($contact->get('field_ci_webpage')->isEmpty());
$this->assertEquals('validated', $contact->field_ci_state->value);

$contact = $this->loadEntityByLabel('rdf_entity', 'Ignacio Boixo', 'contact_information');
$this->assertEquals('Ignacio Boixo', $contact->label());
$this->assertEquals('contact_information', $contact->bundle());
$this->assertEquals('default', $contact->graph->value);
$this->assertEquals('Ignacio Boixo', $contact->field_ci_name->value);
$this->assertTrue($contact->get('field_ci_email')->isEmpty());
$this->assertTrue($contact->get('field_ci_webpage')->isEmpty());
$this->assertEquals('validated', $contact->field_ci_state->value);

$contact = $this->loadEntityByLabel('rdf_entity', 'Romain Loth', 'contact_information');
$this->assertEquals('Romain Loth', $contact->label());
$this->assertEquals('contact_information', $contact->bundle());
$this->assertEquals('default', $contact->graph->value);
$this->assertEquals('Romain Loth', $contact->field_ci_name->value);
$this->assertTrue($contact->get('field_ci_email')->isEmpty());
$this->assertTrue($contact->get('field_ci_webpage')->isEmpty());
$this->assertEquals('validated', $contact->field_ci_state->value);
