<?php

declare(strict_types = 1);

namespace Drupal\joinup_invite\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
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
   *   The called Invitation entity.
   */
  public function setCreatedTime(int $timestamp) : InvitationInterface;

  /**
   * Returns the available statuses for the invitation.
   *
   * @return array
   *   An array of status labels, keyed by status ID.
   */
  public static function getStatuses() : array;

}
