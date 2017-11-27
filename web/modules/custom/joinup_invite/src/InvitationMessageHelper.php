<?php

declare(strict_types = 1);

namespace Drupal\joinup_invite;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\joinup_invite\Entity\InvitationInterface;
use Drupal\joinup_notification\JoinupMessageDeliveryInterface;
use Drupal\message\MessageInterface;

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
   * The helper service for delivering messages.
   *
   * @var \Drupal\joinup_notification\JoinupMessageDeliveryInterface
   */
  protected $messageDelivery;

  /**
   * Constructs a new InvitationMessageHelper service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\joinup_notification\JoinupMessageDeliveryInterface $messageDelivery
   *   The helper service for delivering messages.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, JoinupMessageDeliveryInterface $messageDelivery) {
    $this->entityTypeManager = $entityTypeManager;
    $this->messageDelivery = $messageDelivery;
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
    return $this->messageDelivery
      ->setMessage($message)
      ->setRecipients([$invitation->getOwner()])
      ->sendMail();
  }

}
