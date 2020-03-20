<?php

declare(strict_types = 1);

namespace Drupal\joinup_group;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * An interface for services that provide information about group relations.
 */
interface JoinupGroupRelationInfoInterface {

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
   * Retrieves the content creation option of the parent entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group content entity.
   *
   * @return int
   *   The content creation option. Can be one of the following values:
   *   - \Drupal\joinup_group\ContentCreationOptions::FACILITATORS
   *   - \Drupal\joinup_group\ContentCreationOptions::MEMBERS
   *   - \Drupal\joinup_group\ContentCreationOptions::REGISTERED_USERS
   */
  public function getParentContentCreationOption(EntityInterface $entity): string;

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
   * Returns the memberships of a user for a given bundle.
   *
   * Use this to retrieve for example all the user's collection or solution
   * memberships.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user for which to retrieve the memberships.
   * @param string $entity_type_id
   *   The entity type for which to retrieve the memberships.
   * @param string $bundle_id
   *   The bundle for which to retrieve the memberships.
   * @param array $states
   *   The membership states. Defaults to active memberships.
   *
   * @return \Drupal\og\OgMembershipInterface[]
   *   The memberships.
   */
  public function getUserGroupMembershipsByBundle(AccountInterface $user, string $entity_type_id, string $bundle_id, array $states = [OgMembershipInterface::STATE_ACTIVE]): array;

  /**
   * Returns the entity IDs of all collections.
   *
   * @return string[]
   *   An array of entity IDs.
   */
  public function getCollectionIds(): array;

  /**
   * Returns the entity IDs of all solutions.
   *
   * @return string[]
   *   An array of entity IDs.
   */
  public function getSolutionIds(): array;

  /**
   * Returns the groups that relate to a contact information entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $entity
   *   The contact information entity.
   *
   * @return \Drupal\rdf_entity\RdfInterface[]
   *   A list of rdf entities that reference the given contact information
   *   entity.
   */
  public function getContactInformationRelatedGroups(RdfInterface $entity): array;

}
