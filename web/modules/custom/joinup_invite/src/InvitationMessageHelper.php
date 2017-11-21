<?php

declare(strict_types = 1);

namespace Drupal\joinup_invite;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\joinup_invite\Entity\InvitationInterface;
use Drupal\message\MessageInterface;
use Drupal\message_notify\MessageNotifier;

/**
 * Service that assists in creating and retrieving messages for invitations.
 */
class InvitationMessageHelper implements InvitationMessageHelperInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The message notifier from the message_notify module.
   *
   * @var \Drupal\message_notify\MessageNotifier
   */
  protected $messageNotifier;

  /**
   * Constructs a new InvitationMessageHelper service.
   *
   * @param EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\message_notify\MessageNotifier $messageNotifier
   *   The message notifier from the message_notify module.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, MessageNotifier $messageNotifier) {
    $this->entityTypeManager = $entityTypeManager;
    $this->messageNotifier = $messageNotifier;
  }

  /**
   * {@inheritdoc}
   */
  public function createMessage(InvitationInterface $invitation, string $template, array $arguments) : MessageInterface {
    // Check that the invitation has been saved, since we need to be able to
    // reference its ID.
    if ($invitation->isNew()) {
      throw new \InvalidArgumentException('Messages can only be created for saved invitations.');
    }

    /** @var \Drupal\message\MessageInterface $message */
    $message = $this->entityTypeManager->getStorage('message')->create([
      'template' => $template,
      'arguments' => $arguments,
      'field_invitation' => $invitation->id(),
    ]);

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage(InvitationInterface $invitation, string $template) : ?MessageInterface {
    $messages = $this->entityTypeManager->getStorage('message')->loadByProperties([
      'template' => $template,
      'field_invitation' => $invitation->id(),
    ]);

    return $messages ? reset($messages) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessage(InvitationInterface $invitation, string $template) : bool {
    if (!$message = $this->getMessage($invitation, $template)) {
      return FALSE;
    }
    $options = ['save on success' => FALSE, 'mail' => $invitation->getOwner()->getEmail()];

    return $this->messageNotifier->send($message, $options);
  }

}
