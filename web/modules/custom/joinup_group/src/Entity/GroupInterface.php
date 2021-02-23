<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\joinup_bundle_class\LogoInterface;
use Drupal\joinup_core\Entity\OutdatedContentInterface;
use Drupal\og\OgMembershipInterface;

/**
 * Interface for entities that are groups.
 *
 * This comprises collections and solutions.
 */
interface GroupInterface extends ContentEntityInterface, LogoInterface, OutdatedContentInterface {

  /**
   * Returns the given user's membership for this group entity.
   *
   * @param int $uid
   *   The ID of the user for which to return the membership.
   * @param array $states
   *   (optional) Array with the states to return. Defaults to only returning
   *   active memberships. In order to retrieve all memberships regardless of
   *   state, pass `OgMembershipInterface::ALL_STATES`.
   *
   * @return \Drupal\og\OgMembershipInterface|null
   *   The OgMembership entity, or NULL if the user with the given ID is not a
   *   member.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown when the OG Membership entity type definition is invalid.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown when the OG module is not enabled.
   */
  public function getMembership(int $uid, array $states = [OgMembershipInterface::STATE_ACTIVE]): ?OgMembershipInterface;

  /**
   * Returns the group owners.
   *
   * In Joinup every new group has exactly one owner. However there are still a
   * number of solutions which were migrated from the legacy platform which have
   * multiple owners. Since we enforce a single owner when an ownership transfer
   * is initiated, the number of groups with multiple owners will reduce over
   * time. For the moment though we still need to account for multiple owners.
   *
   * During some special operations it is possible that no group owner is
   * assigned. For example when the group is initially created, during transfer
   * of ownership, etc.
   *
   * @return \Drupal\user\UserInterface[]
   *   The group owners.
   */
  public function getGroupOwners(): array;

  /**
   * Returns the user IDs of the group owners.
   *
   * In Joinup every new group has exactly one owner. However there are still a
   * number of solutions which were migrated from the legacy platform which have
   * multiple owners. Since we enforce a single owner when an ownership transfer
   * is initiated, the number of groups with multiple owners will reduce over
   * time. For the moment though we still need to account for multiple owners.
   *
   * During some special operations it is possible that no group owner is
   * assigned. For example when the group is initially created, during transfer
   * of ownership, etc.
   *
   * @return int[]
   *   The user IDs of the group owners.
   */
  public function getGroupOwnerIds(): array;

  /**
   * Returns whether the user with the given ID is a group owner.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return bool
   *   TRUE if true.
   */
  public function isGroupOwner(int $uid): bool;

  /**
   * Returns whether the user with the given ID is the sole group owner.
   *
   * In Joinup every new group has exactly one owner. However there are still a
   * number of solutions which were migrated from the legacy platform which have
   * multiple owners. In some cases (such as transferring group ownership) we
   * need to be able to ascertain that a user is the sole owner of a group.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return bool
   *   TRUE if true.
   */
  public function isSoleGroupOwner(int $uid): bool;

  /**
   * Checks if the user has at least one role with the given OG permission.
   *
   * This is a simple wrapper around OgMembershipInterface::hasPermission() and
   * should not be relied on for access checks. Use OgAccess to check access.
   *
   * @param int $uid
   *   The ID of the user to check.
   * @param string $permission
   *   The permission string.
   *
   * @return bool
   *   TRUE if the permission is present in at least one of the user's roles in
   *   the group.
   */
  public function hasGroupPermission(int $uid, string $permission): bool;

}
