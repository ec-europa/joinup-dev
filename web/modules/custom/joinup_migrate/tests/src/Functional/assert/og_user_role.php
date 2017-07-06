<?php

/**
 * @file
 * Assertions for 'og_user_role' and 'og_user_role_solution' migration.
 */

use Drupal\og\Entity\OgRole;
use Drupal\og\OgMembershipInterface;

// Asserts the state and roles for a certain membership defined by its group
// entity ID and member user ID.
$assert_og_roles = function ($entity_id, $user_name, $expected_state, array $expected_roles = []) {
  $account = user_load_by_name($user_name);
  /* @var \Drupal\og\OgMembershipInterface[] $memberships */
  $memberships = \Drupal::entityTypeManager()->getStorage('og_membership')
    ->loadByProperties([
      'entity_type' => 'rdf_entity',
      'entity_id' => $entity_id,
      'uid' => $account->id(),
    ]);
  if (!$memberships) {
    $this->fail("No OG membership with 'entity_id' = $entity_id, 'uid' = {$account->id()}");
  }
  $membership = reset($memberships);

  $this->assertEquals($expected_state, $membership->getState());

  $actual_roles = array_map(function (OgRole $role) {
    return $role->id();
  }, $membership->getRoles());

  sort($expected_roles);
  sort($actual_roles);

  $this->assertSame($expected_roles, $actual_roles);
};

// Collection: Members of 'Membership testing'.
$assert_og_roles('http://health.gnu.org', 'meanmicio', OgMembershipInterface::STATE_ACTIVE, [
  'rdf_entity-collection-member',
  'rdf_entity-collection-facilitator',
  'rdf_entity-collection-administrator',
]);
$assert_og_roles('http://health.gnu.org', 'ggonzalezpp', OgMembershipInterface::STATE_ACTIVE, [
  'rdf_entity-collection-member',
]);
$assert_og_roles('http://health.gnu.org', 'ThomasU1', OgMembershipInterface::STATE_PENDING, [
  'rdf_entity-collection-member',
]);

// Collection: Members of 'Collection with 1 entity having custom section'.
/* @var \Drupal\rdf_entity\RdfInterface $collection */
$collection = $this->loadEntityByLabel('rdf_entity', 'Collection with 1 entity having custom section', 'collection');
$assert_og_roles($collection->id(), 'joinup_editor', OgMembershipInterface::STATE_ACTIVE, [
  'rdf_entity-collection-member',
  'rdf_entity-collection-facilitator',
]);

// Solution: Members of 'DCAT application profile for data portals in Europe'.
/* @var \Drupal\rdf_entity\RdfInterface $solution */
$solution = $this->loadEntityByLabel('rdf_entity', 'DCAT application profile for data portals in Europe', 'solution');
$assert_og_roles($solution->id(), 'joinup_semantic_editor', OgMembershipInterface::STATE_ACTIVE, [
  'rdf_entity-solution-member',
  'rdf_entity-solution-facilitator',
  'rdf_entity-solution-administrator',
]);
$assert_og_roles($solution->id(), 'sszekacs', OgMembershipInterface::STATE_ACTIVE, [
  'rdf_entity-solution-member',
  'rdf_entity-solution-facilitator',
]);
