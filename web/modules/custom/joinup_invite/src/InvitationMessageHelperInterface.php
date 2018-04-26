<?php

declare(strict_types = 1);

namespace Drupal\joinup_invite;

use Drupal\joinup_invite\Entity\InvitationInterface;
use Drupal\joinup_notification\EntityMessageHelperInterface;
use Drupal\message\MessageInterface;

/**
 * Interface for services that assist in managing messages for invitations.
 *
 * When creating invitations it is recommended to also send a message to the
 * user to notify them about the pending invitation. If you are building a new
 * invitation, create a new Message template and make sure it has an entity
 * reference field named `field_invitation` that references entities of type
 * `Invitation` and is limited to only accept the bundle of the invitation that
 * you have created. When creating a new notification message through this
 * service, the Invitation will automatically be referenced through that field.
 */
interface InvitationMessageHelperInterface extends EntityMessageHelperInterface {

  /**
   * Creates a new message that is associated with the given invitation.
   *
   * @param \Drupal\joinup_invite\Entity\InvitationInterface $invitation
   *   The invitation that will be referenced in the new message.
   * @param string $template
   *   The message template ID.
   * @param array $arguments
   *   The array of arguments that will be used to replace token-like strings in
   *   the message. The `@invitation:accept_url` and `@invitation:reject_url`
   *   arguments will be added automatically.
   *
   * @return \Drupal\message\MessageInterface
   *   The newly created, unsaved, message.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the passed invitation cannot be referenced since it hasn't
   *   yet been saved and does not have an ID yet.
   */
  public function createMessage(InvitationInterface $invitation, string $template, array $arguments): MessageInterface;

  /**
   * Retrieves the message that is associated with the given invitation.
   *
   * @param \Drupal\joinup_invite\Entity\InvitationInterface $invitation
   *   The invitation that is referenced by the message.
   * @param string $template
   *   The message template ID.
   *
   * @return \Drupal\message\MessageInterface|null
   *   The message, or NULL if no message with the given template is associated
   *   with the given invitation.
   */
  public function getMessage(InvitationInterface $invitation, string $template): ?MessageInterface;

  /**
   * Sends the message that is associated with the given invitation.
   *
   * @param \Drupal\joinup_invite\Entity\InvitationInterface $invitation
   *   The invitation that is referenced by the message.
   * @param string $template
   *   The message template ID.
   *
   * @return bool
   *   TRUE if the message was sent successfully. FALSE otherwise.
   */
  public function sendMessage(InvitationInterface $invitation, string $template): bool;

}
