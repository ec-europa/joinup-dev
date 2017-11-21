<?php

declare(strict_types = 1);

namespace Drupal\joinup_invite\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Invitation entities.
 */
interface InvitationInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * The status value for an invitation that is pending.
   *
   * @var string
   */
  const STATUS_PENDING = 'pending';

  /**
   * The status value for an invitation that has been accepted.
   *
   * @var string
   */
  const STATUS_ACCEPTED = 'accepted';

  /**
   * The status value for an invitation that has been rejected.
   *
   * @var string
   */
  const STATUS_REJECTED = 'rejected';

  /**
   * Returns the entity the user has been invited to.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity.
   */
  public function getEntity() : EntityInterface;

  /**
   * Sets the entity the user will be invited to.
   *
   * It is only allowed to set this on new invitations. Once an invitation has
   * been saved the entity and user can no longer be changed.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to set.
   *
   * @return \Drupal\joinup_invite\Entity\InvitationInterface
   *   The updated Invitation.
   *
   * @throws \Exception
   *   Thrown when attempting to change the entity of an Invitation that has
   *   already been saved.
   */
  public function setEntity(EntityInterface $entity) : InvitationInterface;

  /**
   * Returns the current status of the invitation.
   *
   * @return string
   *   The invitation status. Can be one of 'pending', 'accepted' or 'rejected'.
   */
  public function getStatus() : string;

  /**
   * Sets the invitation status.
   *
   * @param string $status
   *   The invitation status. Can be one of 'pending', 'accepted' or 'rejected'.
   *
   * @return \Drupal\joinup_invite\Entity\InvitationInterface
   *   The updated Invitation.
   *
   * @throws \InvalidArgumentException
   *   Thrown when an invalid status is passed.
   */
  public function setStatus(string $status) : InvitationInterface;

  /**
   * Gets the Invitation creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Invitation.
   */
  public function getCreatedTime() : int;

  /**
   * Sets the Invitation creation timestamp.
   *
   * @param int $timestamp
   *   The Invitation creation timestamp.
   *
   * @return \Drupal\joinup_invite\Entity\InvitationInterface
   *   The updated Invitation.
   */
  public function setCreatedTime(int $timestamp) : InvitationInterface;

  /**
   * Accepts an invitation.
   *
   * @return \Drupal\joinup_invite\Entity\InvitationInterface
   *   The accepted invitation.
   */
  public function accept() : InvitationInterface;

  /**
   * Rejects an invitation.
   *
   * @return \Drupal\joinup_invite\Entity\InvitationInterface
   *   The rejected invitation.
   */
  public function reject() : InvitationInterface;

  /**
   * Returns the available statuses for the invitation.
   *
   * @return array
   *   An array of status labels, keyed by status ID.
   */
  public static function getStatuses() : array;

  /**
   * Returns the invitation that matches the given entity and user.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user.
   * @param string $bundle
   *   The invitation type.
   *
   * @return \Drupal\joinup_invite\Entity\InvitationInterface|null
   *   The invitation, or NULL if the requested invitation doesn't exist.
   */
  public static function loadByEntityAndUser(EntityInterface $entity, AccountInterface $user, string $bundle) : ?InvitationInterface;

}
