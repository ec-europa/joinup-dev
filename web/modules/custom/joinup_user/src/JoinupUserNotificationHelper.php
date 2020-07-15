<?php

declare(strict_types = 1);

namespace Drupal\joinup_user;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup_user\Entity\JoinupUserInterface;

/**
 * Service to assist with sending notifications about actions taken on users.
 */
class JoinupUserNotificationHelper implements JoinupUserNotificationHelperInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Constructs a JoinupUserNotificationHelper service.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager service.
   */
  public function __construct(AccountInterface $current_user, MessengerInterface $messenger, MailManagerInterface $mail_manager) {
    $this->currentUser = $current_user;
    $this->messenger = $messenger;
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function notifyOnAccountChange(JoinupUserInterface $user): void {
    // Drupal allows accounts without E-mail when the account is created by an
    // administrator. We skip them because they cannot receive notifications.
    if (!$user->getEmail()) {
      return;
    }

    // A notification should only be sent when the user account is changed by
    // another user.
    if ($user->isAnonymous() || $this->currentUser->isAnonymous() || $user->id() == $this->currentUser->id()) {
      return;
    }

    // Inform the user that their account was updated.
    $this->mailManager->mail(
      'joinup_user',
      'email_admin_update',
      $user->getEmail(),
      $user->getPreferredLangcode(),
      ['account' => $user]
    );

    $this->messenger->addStatus('The user has been notified that their account has been updated.');
  }

}
