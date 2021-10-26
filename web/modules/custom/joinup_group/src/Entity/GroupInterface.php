<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Entity;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\joinup_bundle_class\LogoInterface;
use Drupal\joinup_bundle_class\ShortIdInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\rdf_entity\RdfInterface;
use Drupal\topic\Entity\TopicReferencingEntityInterface;

/**
 * Interface for entities that are groups.
 *
 * This comprises collections and solutions.
 */
interface GroupInterface extends RdfInterface, LogoInterface, ShortIdInterface, TopicReferencingEntityInterface {

  /**
   * Flag for pre-moderated groups.
   */
  public const PRE_MODERATION = 1;

  /**
   * Flag for post-moderated groups.
   */
  public const POST_MODERATION = 0;

  /**
   * Returns the given user's membership for this group entity.
   *
   * @param int|null $uid
   *   The ID of the user for which to return the membership. If omitted the
   *   membership of the current user will be returned.
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
  public function getMembership(?int $uid = NULL, array $states = [OgMembershipInterface::STATE_ACTIVE]): ?OgMembershipInterface;

  /**
   * Creates a membership for the given user in the current group.
   *
   * @param int|null $uid
   *   The ID of the user that will become a member. Defaults to the current
   *   user.
   * @param string|null $role
   *   The role to assign to the user. Can be either 'member', 'author, or
   *   'facilitator'. Owners cannot be added using this method since every group
   *   has a single owner which is assigned when the group is created or
   *   transferred. Defaults to 'member'.
   * @param string|null $state
   *   The state of the membership. It may be of the following constants:
   *   - OgMembershipInterface::STATE_ACTIVE
   *   - OgMembershipInterface::STATE_PENDING
   *   - OgMembershipInterface::STATE_BLOCKED.
   *   Defaults to the most appropriate state: 'active' for solutions and open
   *   collections, and 'pending' for closed collections.
   *
   * @return \Drupal\og\OgMembershipInterface
   *   The membership that was created.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when an error occurs during the saving of the membership.
   * @throws \Drupal\joinup_group\Exception\MembershipExistsException
   *   Thrown when the user already has a membership.
   * @throws \InvalidArgumentException
   *   Thrown when the passed in role does not exist, when a membership for an
   *   owner is created, or when the passed in user ID does not match an
   *   existing user.
   */
  public function createMembership(?int $uid = NULL, ?string $role = 'member', ?string $state = NULL): OgMembershipInterface;

  /**
   * Determines whether a user has a group permission in the group.
   *
   * The following conditions will result in a positive result:
   * - The user is the global super user (UID 1).
   * - The user has a role in the group that specifically grants the permission.
   * - The user is not a member of the group, and the permission has been
   *   granted to non-members.
   *
   * @param string $permission
   *   The name of the OG permission being checked. This includes both group
   *   level permissions such as 'subscribe without approval' and group content
   *   entity operation permissions such as 'edit own article content'.
   * @param \Drupal\Core\Session\AccountInterface|null $user
   *   (optional) The user to check. Defaults to the current user.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   An access result object.
   */
  public function getGroupAccess(string $permission, ?AccountInterface $user = NULL): AccessResultInterface;

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
   * should not be relied on for access checks. Use ::getGroupAccess() instead.
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

  /**
   * Returns whether the group is moderated.
   *
   * Community content of moderated groups are going through the pre-moderated
   * workflow.
   *
   * @return bool
   *   Whether the group is moderated.
   */
  public function isModerated(): bool;

  /**
   * Returns the field name of the group moderation field.
   *
   * @return string
   *   The field name.
   */
  public function getGroupModerationFieldName(): string;

  /**
   * Returns who can create content in the group.
   *
   * @return string
   *   The content creation option value. Can be one of the following:
   *   - \Drupal\joinup_group\ContentCreationOptions::FACILITATORS_AND_AUTHORS
   *   - \Drupal\joinup_group\ContentCreationOptions::MEMBERS
   *   - \Drupal\joinup_group\ContentCreationOptions::REGISTERED_USERS
   */
  public function getContentCreators(): string;

  /**
   * Returns the field name of the content creation field.
   *
   * @return string
   *   The field name.
   */
  public function getContentCreationFieldName(): string;

  /**
   * Returns recursively all content IDs of this group.
   *
   * WARNING! This method is resource intensive and it's not recommended to be
   * used in a normal page request. It's strongly advised to be used only in
   * operations that support longer requests, such as cron run.
   *
   * @return array
   *   An associative array keyed by the group content entity type ID. Each
   *   value is an associative array keyed by entity bundle and having the node
   *   IDs as values. The array is sorted by keys and, within each bundle, by
   *   entity IDs.
   */
  public function getGroupContentIds(): array;

  /**
   * Returns the message to show to a new member when they join the group.
   *
   * @param \Drupal\og\OgMembershipInterface $membership
   *   The new membership.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The success status message.
   */
  public function getNewMembershipSuccessMessage(OgMembershipInterface $membership): TranslatableMarkup;

  /**
   * Returns the message to show to a member when they attempt to rejoin.
   *
   * @param \Drupal\og\OgMembershipInterface $membership
   *   The existing membership.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The status message.
   */
  public function getExistingMembershipMessage(OgMembershipInterface $membership): TranslatableMarkup;

  /**
   * Returns the number of members in the group.
   *
   * @param array $states
   *   An array of membership states to check. Can contain one or more of:
   *   - OgMembershipInterface::STATE_ACTIVE
   *   - OgMembershipInterface::STATE_PENDING
   *   - OgMembershipInterface::STATE_BLOCKED
   *   Defaults to checking active members.
   *
   * @return int
   *   The number of members.
   */
  public function getMemberCount(array $states = [OgMembershipInterface::STATE_ACTIVE]): int;

}
