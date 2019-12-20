<?php

declare(strict_types = 1);

namespace Drupal\joinup_group;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgMembershipInterface;

/**
 * Helper methods related to the Joinup groups.
 *
 * @package Drupal\joinup_group
 */
class JoinupGroupManager implements JoinupGroupManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The OG membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * Constructs a JoinupRelationshipManager object.
   *
   * @param \Drupal\og\MembershipManagerInterface $membershipManager
   *   The OG membership manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(MembershipManagerInterface $membershipManager, EntityTypeManagerInterface $entityTypeManager) {
    $this->membershipManager = $membershipManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupsWhereSoleOwner(AccountInterface $user): array {
    $groups = [];
    foreach (['collection', 'solution'] as $bundle) {
      $memberships = $this->getUserMembershipsByRole($user, "rdf_entity-{$bundle}-administrator");

      // Prepare a list of groups where the user is the sole owner.
      foreach ($memberships as $membership) {
        $group = $membership->getGroup();
        $owners = $this->getGroupOwners($group);
        if (count($owners) === 1 && array_key_exists($user->id(), $owners)) {
          $groups[$group->id()] = $group;
        }
      }
    }

    return $groups;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserMembershipsByRole(AccountInterface $user, string $role, array $states = [OgMembershipInterface::STATE_ACTIVE]): array {
    $storage = $this->entityTypeManager->getStorage('og_membership');

    // Fetch all the memberships of the user, filtered by role and state.
    $query = $storage->getQuery();
    $query->condition('uid', $user->id());
    $query->condition('roles', $role);
    $query->condition('state', $states, 'IN');
    $result = $query->execute();

    return $storage->loadMultiple($result);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOwners(EntityInterface $entity, array $states = [OgMembershipInterface::STATE_ACTIVE]): array {
    $memberships = $this->membershipManager->getGroupMembershipsByRoleNames($entity, ['administrator'], $states);

    $users = [];
    foreach ($memberships as $membership) {
      $user = $membership->getOwner();
      if (!empty($user)) {
        $users[$user->id()] = $user;
      }
    }

    return $users;
  }

}
