<?php

namespace Drupal\joinup_group;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\OgMembershipInterface;

/**
 * The interface for the JoinupGroupManager service.
 */
interface JoinupGroupManagerInterface {

  /**
   * Retrieves all the collections where a user is the sole owner.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to retrieve memberships for.
   *
   * @return \Drupal\rdf_entity\Entity\Rdf[]
   *   An array of collections.
   */
  public function getGroupsWhereSoleOwner(AccountInterface $user): array;

  /**
   * Retrieves all the user memberships with a certain role and state.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to get the memberships for.
   * @param string $role
   *   The role id.
   * @param array $states
   *   (optional) An array of membership states to retrieve. Defaults to active.
   *
   * @return \Drupal\og\OgMembershipInterface[]
   *   An array of OG memberships that match the criteria.
   */
  public function getUserMembershipsByRole(AccountInterface $user, string $role, array $states = [OgMembershipInterface::STATE_ACTIVE]): array;

  /**
   * Retrieves all the users that have the administrator role in a group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group entity.
   * @param array $states
   *   (optional) An array of membership states to retrieve. Defaults to active.
   *
   * @return array
   *   An array of users that are administrators of the entity group.
   */
  public function getGroupOwners(EntityInterface $entity, array $states = [OgMembershipInterface::STATE_ACTIVE]): array;

}
