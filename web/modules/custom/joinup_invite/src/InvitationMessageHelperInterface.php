<?php

declare(strict_types = 1);

namespace Drupal\joinup_invite;

use Drupal\joinup_invite\Entity\InvitationInterface;
use Drupal\message\MessageInterface;

/**
 * Interface for services that assist in managing messages for invitations.
 */
interface InvitationMessageHelperInterface {

  /**
   * Creates a new message that is associated with the given invitation.
   *
   * @param \Drupal\joinup_invite\Entity\InvitationInterface $invitation
   *   The invitation that will be referenced in the new message.
   * @param string $template
   *   The message template ID.
   * @param array $arguments
   *   The array of arguments that will be used to replace token-like strings in
   *   the message.
   *
   * @return \Drupal\message\MessageInterface
   *   The newly created, unsaved, message.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the passed invitation cannot be referenced since it hasn't
   *   yet been saved and does not have an ID yet.
   */
  public function createMessage(InvitationInterface $invitation, string $template, array $arguments): MessageInterface;

  public function getMessage(InvitationInterface $invitation, string $template): ?MessageInterface;

  public function sendMessage(InvitationInterface $invitation, string $template): bool;

}
