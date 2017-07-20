<?php

/**
 * @file
 * Assertions for 'owner' migration.
 */

// Imported content check.
/* @var \Drupal\rdf_entity\RdfInterface $owner */
$owner = $this->loadEntityByLabel('rdf_entity', 'Ordnance Survey', 'owner');
$this->assertEquals('Ordnance Survey', $owner->label());
$this->assertEquals('owner', $owner->bundle());
$this->assertReferences(['National authority'], $owner->field_owner_type);
$this->assertEquals('validated', $owner->field_owner_state->value);
$this->assertRedirects([
  'node/58692',
  'catalogue/publisher/ordnance-survey',
], $owner);

$owner = $this->loadEntityByLabel('rdf_entity', 'Forumstandaardisatie.nl', 'owner');
$this->assertEquals('Forumstandaardisatie.nl', $owner->label());
$this->assertEquals('owner', $owner->bundle());
$this->assertReferences(['National authority'], $owner->field_owner_type);
$this->assertEquals('validated', $owner->field_owner_state->value);
$this->assertRedirects([
  'node/63886',
  'catalogue/publisher/forumstandaardisatienl',
], $owner);

$owner = $this->loadEntityByLabel('rdf_entity', 'Geodetic Institute of Slovenia', 'owner');
$this->assertEquals('Geodetic Institute of Slovenia', $owner->label());
$this->assertEquals('owner', $owner->bundle());
$this->assertReferences(['Academia/Scientific organisation'], $owner->field_owner_type);
$this->assertEquals('validated', $owner->field_owner_state->value);
$this->assertRedirects([
  'node/137962',
  'catalogue/publisher/geodetic-institute-slovenia',
], $owner);

$owner = $this->loadEntityByLabel('rdf_entity', 'European Banking Authority', 'owner');
$this->assertEquals('European Banking Authority', $owner->label());
$this->assertEquals('owner', $owner->bundle());
$this->assertReferences(['Supra-national authority'], $owner->field_owner_type);
$this->assertEquals('validated', $owner->field_owner_state->value);
$this->assertRedirects([
  'node/59173',
  'catalogue/publisher/european-banking-authority',
], $owner);

$owner = $this->loadEntityByLabel('rdf_entity', 'World Wide Web Consortium', 'owner');
$this->assertEquals('World Wide Web Consortium', $owner->label());
$this->assertEquals('owner', $owner->bundle());
$this->assertReferences(['Standardisation body'], $owner->field_owner_type);
$this->assertEquals('validated', $owner->field_owner_state->value);
$this->assertRedirects([
  'node/55898',
  'catalogue/publisher/world-wide-web-consortium',
], $owner);

$owner = $this->loadEntityByLabel('rdf_entity', 'La forja de Guadalinex', 'owner');
$this->assertEquals('La forja de Guadalinex', $owner->label());
$this->assertEquals('owner', $owner->bundle());
$this->assertTrue($owner->get('field_owner_type')->isEmpty());
$this->assertEquals('validated', $owner->field_owner_state->value);
$this->assertRedirects([
  'node/89762',
  'catalogue/publisher/la-forja-de-guadalinex',
], $owner);

$owner = $this->loadEntityByLabel('rdf_entity', 'Dark Side of The Force', 'owner');
$this->assertEquals('Dark Side of The Force', $owner->label());
$this->assertEquals('owner', $owner->bundle());
$this->assertReferences(['Supra-national authority'], $owner->field_owner_type);
$this->assertEquals('validated', $owner->field_owner_state->value);
$this->assertRedirects([], $owner);

$owner = $this->loadEntityByLabel('rdf_entity', 'ACME University', 'owner');
$this->assertEquals('ACME University', $owner->label());
$this->assertEquals('owner', $owner->bundle());
$this->assertReferences(['Academia/Scientific organisation'], $owner->field_owner_type);
$this->assertEquals('validated', $owner->field_owner_state->value);
$this->assertRedirects([], $owner);
