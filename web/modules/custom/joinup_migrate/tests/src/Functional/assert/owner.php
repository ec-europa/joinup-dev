<?php

/**
 * @file
 * Assertions for 'owner' migration.
 */

// Migration counts.
$this->assertTotalCount('owner', 5);
$this->assertSuccessCount('owner', 5);
$this->assertTotalCount('owner_text', 2);
$this->assertSuccessCount('owner_text', 2);

// Imported content check.
/* @var \Drupal\rdf_entity\RdfInterface $owner */
$owner = $this->loadEntityByLabel('rdf_entity', 'Ordnance Survey', 'owner');
$this->assertEquals('Ordnance Survey', $owner->label());
$this->assertEquals('owner', $owner->bundle());
$this->assertReferences(['National authority'], $owner->field_owner_type);
$this->assertEquals('validated', $owner->field_owner_state->value);

$owner = $this->loadEntityByLabel('rdf_entity', 'Forumstandaardisatie.nl', 'owner');
$this->assertEquals('Forumstandaardisatie.nl', $owner->label());
$this->assertEquals('owner', $owner->bundle());
$this->assertReferences(['National authority'], $owner->field_owner_type);
$this->assertEquals('validated', $owner->field_owner_state->value);

$owner = $this->loadEntityByLabel('rdf_entity', 'Geodetic Institute of Slovenia', 'owner');
$this->assertEquals('Geodetic Institute of Slovenia', $owner->label());
$this->assertEquals('owner', $owner->bundle());
$this->assertReferences(['Academia/Scientific organisation'], $owner->field_owner_type);
$this->assertEquals('validated', $owner->field_owner_state->value);

$owner = $this->loadEntityByLabel('rdf_entity', 'Dark Side of The Force', 'owner');
$this->assertEquals('Dark Side of The Force', $owner->label());
$this->assertEquals('owner', $owner->bundle());
$this->assertReferences(['Supra-national authority'], $owner->field_owner_type);
$this->assertEquals('validated', $owner->field_owner_state->value);

$owner = $this->loadEntityByLabel('rdf_entity', 'ACME University', 'owner');
$this->assertEquals('ACME University', $owner->label());
$this->assertEquals('owner', $owner->bundle());
$this->assertReferences(['Academia/Scientific organisation'], $owner->field_owner_type);
$this->assertEquals('validated', $owner->field_owner_state->value);

$owner = $this->loadEntityByLabel('rdf_entity', 'World Wide Web Consortium', 'owner');
$this->assertEquals('World Wide Web Consortium', $owner->label());
$this->assertEquals('owner', $owner->bundle());
$this->assertReferences(['Standardisation body'], $owner->field_owner_type);
$this->assertEquals('validated', $owner->field_owner_state->value);

$owner = $this->loadEntityByLabel('rdf_entity', 'European Banking Authority', 'owner');
$this->assertEquals('European Banking Authority', $owner->label());
$this->assertEquals('owner', $owner->bundle());
$this->assertReferences(['Supra-national authority'], $owner->field_owner_type);
$this->assertEquals('validated', $owner->field_owner_state->value);
