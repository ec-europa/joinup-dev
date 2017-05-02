<?php

/**
 * @file
 * Assertions for 'owner' migration.
 */

// Migration counts.
$this->assertTotalCount('owner', 2);
$this->assertSuccessCount('owner', 2);

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
