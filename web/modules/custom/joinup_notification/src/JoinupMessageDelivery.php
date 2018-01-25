<?php

declare(strict_types = 1);

namespace Drupal\joinup_notification;

use Drupal\message\Entity\Message;
use Drupal\message\MessageInterface;
use Drupal\message_notify\MessageNotifier;
use Drupal\user\UserInterface;

/**
 * Provides a service class for creating and delivering messages.
 */
class JoinupMessageDelivery implements JoinupMessageDeliveryInterface {

  /**
   * A list of message digest notifier plugin IDs.
   *
   * @var array
   */
  const DIGEST_NOTIFIER_IDS = [
    'daily' => 'message_digest:daily',
    'weekly' => 'message_digest:weekly',
    'monthly' => 'message_digest:monthly',
  ];

  /**
   * The message notifier service.
   *
   * @var \Drupal\message_notify\MessageNotifier
   */
  protected $messageNotifier;

  /**
   * Constructs a new Joinup deliver service object.
   *
   * @param \Drupal\message_notify\MessageNotifier $message_notifier
   *   The message notifier service.
   */
  public function __construct(MessageNotifier $message_notifier) {
    $this->messageNotifier = $message_notifier;
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessageToMultipleUsers(MessageInterface $message, array $accounts, array $notifier_options = []): bool {
    $recipients_metadata = [];
    /** @var \Drupal\user\UserInterface $account */
    foreach ($accounts as $account) {
      // Throw an exception when attempting to send mails to anonymous users or
      // users that for some reason do not have an e-mail address set.
      if ($account->isAnonymous()) {
        throw new \LogicException('Cannot send mail to an anonymous user.');
      }
      $mail = $account->getEmail();
      if (empty($mail)) {
        throw new \LogicException('Cannot send mail to a user that does not have an e-mail address.');
      }

      // By keying on the user ID we can avoid that a user might get the message
      // more than once.
      $recipients_metadata[$account->id()] = [
        'options' => $notifier_options + ['save on success' => FALSE, 'mail' => $mail],
        'notifier' => 'email',
      ];
    }

    return $this->sendMessage($message, $recipients_metadata);
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessageToEmailAddresses(MessageInterface $message, array $mails, array $notifier_options = []): bool {
    $recipients_metadata = [];

    // Ensure uniqueness so that the message is not delivered multiple times to
    // the same address.
    foreach (array_unique($mails) as $mail) {
      $recipients_metadata[] = [
        'options' => $notifier_options + ['save on success' => FALSE, 'mail' => $mail],
        'notifier' => 'email',
      ];
    }

    return $this->sendMessage($message, $recipients_metadata);
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessageTemplateToMultipleUsers(string $message_template, array $arguments, array $accounts, array $notifier_options = []): bool {
    $message = $this->createMessage($message_template, $arguments);
    return $this->sendMessageToMultipleUsers($message, $accounts, $notifier_options);
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessageTemplateToEmailAddresses(string $message_template, array $arguments, array $mails, array $notifier_options = []): bool {
    $message = $this->createMessage($message_template, $arguments);
    return $this->sendMessageToEmailAddresses($message, $mails, $notifier_options);
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessageTemplateToUser(string $message_template, array $arguments, UserInterface $account, array $notifier_options = [], bool $digest = TRUE): bool {
    if ($account->isAnonymous()) {
      throw new \LogicException('Cannot send mail to an anonymous user.');
    }

    $message = $this->createMessage($message_template, $arguments);
    $message->setOwner($account);
    $recipients_metadata = [
      [
        'options' => $notifier_options,
        'notifier' => $digest ? $this->getNotifierId($account) : 'email',
      ],
    ];
    return $this->sendMessage($message, $recipients_metadata);
  }

  /**
   * Returns the message digest notifier plugin ID for the given user.
   *
   * Users may configure the frequency they wish to receive a message digest.
   * This returns the corresponding digest notifier plugin ID.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account for which to return the plugin ID.
   *
   * @return string
   *   The plugin ID.
   */
  protected function getNotifierId(UserInterface $account): string {
    $frequency = $account->get('field_user_frequency')->value;
    if (array_key_exists($frequency, self::DIGEST_NOTIFIER_IDS)) {
      return self::DIGEST_NOTIFIER_IDS[$frequency];
    }

    // Use standard email notification if the user didn't choose a frequency.
    return 'email';
  }

  /**
   * Sends the given message to the given recipients.
   *
   * @param \Drupal\message\MessageInterface $message
   *   The message to send.
   * @param array $recipients_metadata
   *   An array of recipient data, each item an associative array with the
   *   following keys:
   *   - options: An associative array of options for the message notifier.
   *   - notifier: The plugin ID of the message notifier to use.
   *
   * @return bool
   *   Whether or not all messages were successfully sent.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when a message could not be saved.
   */
  protected function sendMessage(MessageInterface $message, array $recipients_metadata): bool {
    // If the message is not saved, do this right now.
    if ($message->isNew()) {
      $message->save();
    }

    return array_reduce($recipients_metadata, function (bool $success, array $recipient_metadata) use ($message): bool {
      return $this->messageNotifier->send($message, $recipient_metadata['options'], $recipient_metadata['notifier']) && $success;
    }, TRUE);
  }

  /**
   * Returns a new Message entity from the given template and arguments.
   *
   * @param string $message_template
   *   The message template to use for creating the message.
   * @param array $arguments
   *   The arguments array to set on the message.
   *
   * @return \Drupal\message\MessageInterface
   *   The message.
   */
  protected function createMessage(string $message_template, array $arguments): MessageInterface {
    $message = Message::create(['template' => $message_template]);
    $message->setArguments($arguments);
    return $message;
  }

}
