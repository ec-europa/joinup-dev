<?php

declare(strict_types = 1);

namespace Drupal\joinup\Traits;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;
use Drupal\rdf_entity\RdfInterface;
use PHPUnit\Framework\Assert;

/**
 * Contains helper methods regarding the organic groups.
 */
trait OgTrait {

  /**
   * Creates an Og membership to a group optionally assigning roles as well.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to be assigned as a group member.
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The organic group entity.
   * @param \Drupal\og\Entity\OgRole[] $roles
   *   An array of OgRoles to be passed to the membership.
   * @param string|null $state
   *   Optional state to assign to the membership. Can be one of:
   *   - OgMembershipInterface::STATE_ACTIVE
   *   - OgMembershipInterface::STATE_PENDING
   *   - OgMembershipInterface::STATE_BLOCKED.
   * @param int|null $created
   *   (Optional) The created time of the membership.
   *
   * @throws \Exception
   *    Throws an exception when the user is anonymous or the entity is not a
   *    group.
   */
  protected function subscribeUserToGroup(AccountInterface $user, EntityInterface $group, array $roles = [], ?string $state = NULL, ?int $created = NULL): void {
    if (!Og::isGroup($group->getEntityTypeId(), $group->bundle())) {
      throw new \Exception("The {$group->label()} is not a group.");
    }

    // If a membership already exists, load it. Otherwise create a new one.
    /** @var \Drupal\og\MembershipManagerInterface $membership_manager */
    $membership_manager = \Drupal::service('og.membership_manager');
    $membership = $membership_manager->getMembership($group, $user->id(), OgMembershipInterface::ALL_STATES);
    if (!$membership) {
      $membership = OgMembership::create()
        ->setOwner($user)
        ->setGroup($group);
    }
    if (!empty($created)) {
      $membership->setCreatedTime($created);
    }
    if (!empty($state)) {
      $membership->setState($state);
    }

    $membership->setRoles($roles);
    $membership->save();
  }

  /**
   * Converts role names into og roles by adding the appropriate prefix.
   *
   * This function does not test if the entity is a group. It merely serves as
   * a name conversion method.
   *
   * @param array $roles
   *   An array of roles to convert names.
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group entity.
   *
   * @return array
   *   An array with the converted names.
   */
  protected function convertOgRoleNamesToIds(array $roles, EntityInterface $group): array {
    $role_prefix = $group->getEntityTypeId() . '-' . $group->bundle() . '-';
    foreach ($roles as $key => $role) {
      // What is called a "collection owner" or a "solution owner" in Joinup, is
      // known as an "administrator" in OG.
      $role = $role === 'owner' ? 'administrator' : $role;
      $roles[$key] = $role_prefix . $role;
    }

    return $roles;
  }

  /**
   * Asserts that a group is owned by a user.
   *
   * An ownership is defined as having a specific set of roles in that group.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to be checked.
   * @param \Drupal\rdf_entity\RdfInterface $group
   *   The group entity. In this project, only rdf entities are groups.
   * @param array $roles
   *   An array of roles to be checked. Roles must be passed as simple names
   *    and not as full IDs. Names will be converted accordingly to IDs.
   *
   * @throws \Exception
   *    Throws exception when the user is not a member or is not an owner.
   */
  protected function assertOgGroupOwnership(AccountInterface $user, RdfInterface $group, array $roles): void {
    $membership = Og::getMembership($group, $user);
    if (empty($membership)) {
      throw new \Exception("User {$user->getAccountName()} is not a member of the {$group->label()} group.");
    }

    $roles = $this->convertOgRoleNamesToIds($roles, $group);
    if (array_intersect($roles, $membership->getRolesIds()) != $roles) {
      throw new \Exception("User {$user->getAccountName()} is not the owner of the {$group->label()} group.");
    }
  }

  /**
   * Returns the OgRole objects identified by the given role names.
   *
   * @param array $roles
   *   An array of role names for which to return the roles.
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group entity to which the roles belong.
   *
   * @return \Drupal\og\Entity\OgRole[]
   *   The OgRole objects.
   */
  protected function getOgRoles(array $roles, EntityInterface $group): array {
    $ids = $this->convertOgRoleNamesToIds($roles, $group);
    return array_values(OgRole::loadMultiple($ids));
  }

  /**
   * Checks that the given group has the expected number of group content items.
   *
   * @param int $count
   *   The number of group content items that are expected to be associated with
   *   the group.
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group to check.
   * @param string $group_content_entity_type_id
   *   The entity type ID of the group content items.
   * @param string $group_content_bundle_id
   *   The bundle ID of the group content items.
   *
   * @throws \Exception
   *   Thrown when the actual number of group content items does not match the
   *   expectation.
   */
  protected function assertGroupContentCount(int $count, EntityInterface $group, string $group_content_entity_type_id, string $group_content_bundle_id): void {
    /** @var \Drupal\og\MembershipManagerInterface $membership_manager */
    $membership_manager = \Drupal::service('og.membership_manager');
    $ids = $membership_manager->getGroupContentIds($group, [
      $group_content_entity_type_id,
    ])[$group_content_entity_type_id];

    $result = [];
    if (!empty($ids)) {
      $entity_type_manager = \Drupal::entityTypeManager();
      $entity_type = $entity_type_manager->getDefinition($group_content_entity_type_id);
      $result = $entity_type_manager
        ->getStorage($group_content_entity_type_id)
        ->getQuery()
        ->condition($entity_type->getKey('bundle'), $group_content_bundle_id)
        ->condition($entity_type->getKey('id'), $ids, 'IN')
        ->execute();
    }
    $actual = count($result);

    if ($actual != $count) {
      throw new \Exception("Wrong number of $group_content_bundle_id group content. Expected number: $count, actual number: $actual.");
    }
  }

  /**
   * Checks if the given content belongs to the given parent rdf entity.
   *
   * If there are multiple entities or parents with the same title, then
   * only the first one is checked.
   *
   * @param string $parent
   *   The name of the parent rdf entity.
   * @param string $parent_bundle
   *   The bundle of the parent rdf entity.
   * @param string $content
   *   The title of the group content.
   * @param string $content_bundle
   *   The bundle of the group content.
   *
   * @throws \Exception
   *   Thrown when a event with the given title does not exist.
   */
  public function assertOgMembership(string $parent, string $parent_bundle, string $content, string $content_bundle): void {
    $parent = $this->getRdfEntityByLabel($parent, $parent_bundle);

    $results = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['title' => $content, 'type' => $content_bundle]);
    /** @var \Drupal\node\NodeInterface $content */
    $content = reset($results);

    if (empty($content)) {
      throw new \Exception("The $content_bundle titled '$content' was not found.");
    }

    $membership_manager = \Drupal::service('og.membership_manager');
    $group_ids = $membership_manager->getGroupIds($content, $parent->getEntityTypeId(), $parent_bundle);
    if (!empty($group_ids) && in_array($parent->id(), $group_ids[$parent->getENtityTypeId()])) {
      // Test passes.
      return;
    }

    throw new \Exception("The $content_bundle '$content' is not associated with the '{$parent->label()}' {$parent_bundle}.");
  }

  /**
   * Creates a group membership using the given group and table values.
   *
   * @param \Drupal\rdf_entity\RdfInterface $group
   *   The group to which the user will be subscribed.
   * @param array $values
   *   Associative array containing table values from a Behat scenario. The
   *   following keys are supported:
   *   - 'user': The user name.
   *   - 'roles': Optional comma separated list of roles to assign to the user.
   *     If omitted the user will have the 'member' role.
   *   - 'state': Optional state. Can be 'active', 'pending', or 'blocked'. If
   *     omitted it will default to 'active'.
   *
   * @throws \Exception
   *   Thrown when a user with the given user name is not found.
   */
  protected function givenUserMembership(RdfInterface $group, array $values): void {
    // Load member.
    $member = user_load_by_name($values['user']);
    if (empty($member)) {
      throw new \Exception("User " . $values['user'] . " not found.");
    }

    // Convert role names to role IDs.
    $roles = [];
    if (!empty($values['roles'])) {
      $role_names = explode(',', $values['roles']);
      $role_names = array_map('trim', $role_names);
      // Every owner is also a facilitator. In Joinup the facilitator role is
      // assigned to the owner when they create the collection. Since in this
      // step the collections are already created, mimick this behaviour by
      // making sure every owner also has the 'facilitator' role.
      if (in_array('owner', $role_names) && !in_array('facilitator', $role_names)) {
        $role_names[] = 'facilitator';
      }
      $roles = $this->getOgRoles($role_names, $group);
    }

    $state = !empty($values['state']) ? $values['state'] : NULL;
    $timestamp = !empty($values['created']) ? strtotime($values['created']) : NULL;
    $this->subscribeUserToGroup($member, $group, $roles, $state, $timestamp);
  }

  /**
   * Returns the group membership for a given user and group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group to get the membership for.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to get the membership for.
   * @param array $states
   *   (optional) Array with the state to return. Defaults to active.
   *
   * @return \Drupal\og\OgMembershipInterface
   *   The OgMembership entity.
   *
   * @throws \Exception
   *   Thrown if a membership with the given criteria is not found.
   */
  protected function getMembershipByGroupAndUser(EntityInterface $group, AccountInterface $user, array $states = [OgMembershipInterface::STATE_ACTIVE]): OgMembershipInterface {
    // Make sure we don't get false positives on previously cached results.
    self::resetCache();

    /** @var \Drupal\og\MembershipManagerInterface $membership_manager */
    $membership_manager = \Drupal::service('og.membership_manager');
    $membership = $membership_manager->getMembership($group, $user->id(), $states);
    if (empty($membership)) {
      throw new \Exception("Og membership for user {$user->getDisplayName()} in group {$group->label()} was not found.");
    }

    return $membership;
  }

  /**
   * Checks that the user has permission to perform the operation on the group.
   *
   * @param bool $expected_result
   *   Whether or not the user should have permission to perform the operation.
   * @param string $operation
   *   The operation to perform.
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group on which to perform the operation.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user performing the operation.
   *
   * @throws \Exception
   *   When the access to perform the operation is not as expected.
   */
  protected function assertGroupEntityOperation(bool $expected_result, string $operation, EntityInterface $group, AccountInterface $user): void {
    $user_name = $user->getAccountName();
    $group_name = $group->label();
    $message = "The $operation operation should " . ($expected_result ? '' : 'not ') . "be accessible for $user_name in $group_name";
    Assert::assertEquals($expected_result, $group->access($operation, $user), $message);
  }

  /**
   * Clears the OG related caches.
   */
  protected function resetCache(): void {
    \Drupal::entityTypeManager()->getStorage('og_membership')->resetCache();
    Og::reset();
    parent::clearStaticCaches();
    \Drupal::service('cache.static')->deleteAll();
  }

}
