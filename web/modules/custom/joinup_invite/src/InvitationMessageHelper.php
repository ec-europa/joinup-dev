<?php

declare(strict_types = 1);

namespace Drupal\joinup_invite;

use Drupal\Core\Url;
use Drupal\joinup_invite\Controller\InvitationController;
use Drupal\joinup_invite\Entity\InvitationInterface;
use Drupal\joinup_notification\EntityMessageHelperInterface;
use Drupal\joinup_notification\JoinupMessageDeliveryInterface;
use Drupal\message\MessageInterface;

/**
 * Service that assists in creating and retrieving messages for invitations.
 */
class InvitationMessageHelper implements InvitationMessageHelperInterface {

  /**
   * The entity message helper service.
   *
   * @var \Drupal\joinup_notification\EntityMessageHelperInterface
   */
  protected $entityMessageHelper;

  /**
   * The helper service for delivering messages.
   *
   * @var \Drupal\joinup_notification\JoinupMessageDeliveryInterface
   */
  protected $messageDelivery;

  /**
   * Constructs a new InvitationMessageHelper service.
   *
   * @param \Drupal\joinup_notification\EntityMessageHelperInterface $entityMessageHelper
   *   The entity message helper service.
   * @param \Drupal\joinup_notification\JoinupMessageDeliveryInterface $messageDelivery
   *   The helper service for delivering messages.
   */
  public function __construct(EntityMessageHelperInterface $entityMessageHelper, JoinupMessageDeliveryInterface $messageDelivery) {
    $this->entityMessageHelper = $entityMessageHelper;
    $this->messageDelivery = $messageDelivery;
  }

  /**
   * {@inheritdoc}
   */
  public function createMessage(InvitationInterface $invitation, string $template, array $arguments): MessageInterface {
    // Add defaults `@invitation:accept_url` and `@invitation:reject_url`.
    $arguments += $this->getDefaultArguments($invitation);

    return $this->entityMessageHelper->createMessage($invitation, $template, $arguments, 'field_invitation');
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage(InvitationInterface $invitation, string $template): ?MessageInterface {
    $messages = $this->entityMessageHelper->getMessages($invitation, $template, 'field_invitation', [], 1);
    return $messages ? reset($messages) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessage(InvitationInterface $invitation, string $template): bool {
    if (!$message = $this->getMessage($invitation, $template)) {
      return FALSE;
    }
    return $this->messageDelivery->sendMessageToMultipleUsers($message, [$invitation->getRecipient()]);
  }

  /**
   * Returns default arguments for invitation messages.
   *
   * @param \Drupal\joinup_invite\Entity\InvitationInterface $invitation
   *   The invitation for which to create the default arguments.
   *
   * @return array
   *   An associative array of default arguments, keyed by argument ID.
   */
  protected function getDefaultArguments(InvitationInterface $invitation): array {
    $arguments = [];

    foreach (['accept', 'reject'] as $action) {
      $url_arguments = [
        'invitation' => $invitation->id(),
        'action' => $action,
        'hash' => InvitationController::generateHash($invitation, $action),
      ];
      $url_options = ['absolute' => TRUE];
      $arguments["@invitation:${action}_url"] = Url::fromRoute('joinup_invite.update_invitation', $url_arguments, $url_options)->toString();
    }

    return $arguments;
  }

}
