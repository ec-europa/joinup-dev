<?php

declare(strict_types = 1);

namespace Drupal\joinup_core;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * An interface for Joinup relation manager services.
 */
interface JoinupRelationManagerInterface {

  /**
   * Retrieves the parent of the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group content entity.
   *
   * @return \Drupal\rdf_entity\RdfInterface|null
   *   The rdf entity the passed entity belongs to, or NULL when no group is
   *    found.
   */
  public function getParent(EntityInterface $entity): ?RdfInterface;

  /**
   * Retrieves the moderation state of the parent.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group content entity.
   *
   * @return int
   *   The moderation status. Can be one of the following values:
   *   - NodeWorkflowAccessControlHandler::PRE_MODERATION
   *   - NodeWorkflowAccessControlHandler::POST_MODERATION
   */
  public function getParentModeration(EntityInterface $entity): ?int;

  /**
   * Retrieves the state of the parent.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group content entity.
   *
   * @return string
   *   The state of the parent entity.
   */
  public function getParentState(EntityInterface $entity): string;

  /**
   * Retrieves the eLibrary creation option of the parent.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group content entity.
   *
   * @return int
   *   The eLibrary creation option. Can be one of the following values:
   *   - \Drupal\joinup_core\ELibraryCreationOptions::FACILITATORS
   *   - \Drupal\joinup_core\ELibraryCreationOptions::MEMBERS
   *   - \Drupal\joinup_core\ELibraryCreationOptions::REGISTERED_USERS
   */
  // @codingStandardsIgnoreLine
  public function getParentELibraryCreationOption(EntityInterface $entity): int;

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

  /**
   * Retrieves all the members with any role in a certain group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group entity.
   * @param array $states
   *   (optional) An array of membership states to retrieve. Defaults to active.
   *
   * @return array
   *   An array of users that are members of the entity group.
   */
  public function getGroupUsers(EntityInterface $entity, array $states = [OgMembershipInterface::STATE_ACTIVE]): array;

  /**
   * Retrieves all the memberships of a certain entity group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group entity.
   * @param array $states
   *   (optional) An array of membership states to retrieve. Defaults to active.
   *
   * @return \Drupal\og\OgMembershipInterface[]
   *   The memberships of the group.
   */
  public function getGroupMemberships(EntityInterface $entity, array $states = [OgMembershipInterface::STATE_ACTIVE]): array;

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
   * Retrieves all the collections where a user is the sole owner.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to retrieve memberships for.
   *
   * @return \Drupal\rdf_entity\Entity\Rdf[]
   *   An array of collections.
   */
  public function getCollectionsWhereSoleOwner(AccountInterface $user): array;

}
