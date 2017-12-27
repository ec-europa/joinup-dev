<?php

declare(strict_types = 1);

namespace Drupal\joinup_notification;

use Drupal\message\Entity\Message;
use Drupal\message\MessageInterface;
use Drupal\message_notify\MessageNotifier;
use Drupal\user\UserInterface;

/**
 * Provides a service class for creating and delivering messages.
 *
 * @todo Since this class is a service it acts as a singleton and all data that
 *   is stored in properties will be persisted and be present the next time the
 *   service is called. This will cause problems if this is used more than once,
 *   especially because if different calls store data on both the `$accounts`
 *   and `$mails` properties.
 *
 * @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4169
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
   * The message to be delivered.
   *
   * @var \Drupal\message\MessageInterface
   */
  protected $message;

  /**
   * A list of user accounts acting as recipients for the message.
   *
   * @var \Drupal\user\UserInterface[]
   */
  protected $accounts = [];

  /**
   * The message notifier service.
   *
   * @var \Drupal\message_notify\MessageNotifier
   */
  protected $messageNotifier;

  /**
   * Additional arguments.
   *
   * @var array
   */
  protected $arguments = [];

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
  public function sendMessageToUsers(MessageInterface $message, array $accounts, bool $digest = FALSE): bool {
    $recipients = [];
    /** @var \Drupal\user\UserInterface $account */
    foreach ($accounts as $account) {
      // Don't send mails to anonymous users or users that for some reason do
      // not have an e-mail address set.
      $mail = $account->getEmail();
      if ($account->isAnonymous() || empty($mail)) {
        continue;
      }
      // By keying on the user ID we can avoid that a user might get the message
      // more than once.
      $recipients[$account->id()] = [
        'mail' => $mail,
        'notifier' => $digest ? $this->getNotifierId($account) : 'email',
      ];
    }

    return $this->sendMessage($message, $recipients);
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessageToEmailAddresses(MessageInterface $message, array $mails): bool {
    $recipients = [];

    // Ensure uniqueness so that the message is not delivered multiple times to
    // the same address.
    foreach (array_unique($mails) as $mail) {
      $recipients[] = [
        'mail' => $mail,
        'notifier' => 'email',
      ];
    }

    return $this->sendMessage($message, $recipients);
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
   * @param array $recipients
   *   An array of recipient data, each item an associative array with the
   *   following keys:
   *   - mail: The e-mail address the message should be sent to.
   *   - notifier: The plugin ID of the message notifier to use.
   *
   * @return bool
   *   Whether or not all messages were successfully sent.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when a message could not be saved.
   */
  protected function sendMessage(MessageInterface $message, array $recipients): bool {
    // If the message is not saved, do this right now.
    if ($message->isNew()) {
      $message->save();
    }

    return array_reduce($recipients, function (bool $success, array $recipient) use ($message): bool {
      $options = ['save on success' => FALSE, 'mail' => $recipient['mail']];
      return $this->messageNotifier->send($message, $options, $recipient['notifier']) && $success;
    }, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function createMessage(string $message_template, array $values = []): JoinupMessageDeliveryInterface {
    // If the template was passed in $values, $message_template take precedence.
    $values = ['template' => $message_template] + $values;
    $this->message = Message::create($values);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setArguments(array $arguments): JoinupMessageDeliveryInterface {
    $this->arguments = $arguments;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setRecipients(array $accounts): JoinupMessageDeliveryInterface {
    $this->accounts = $accounts;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function sendMail(): bool {
    if (empty($this->message) || !$this->message instanceof MessageInterface) {
      throw new \RuntimeException("Message entity not set or is invalid. Use ::setMessage() to set a message entity or ::createMessage() to create one.");
    }

    $message_arguments = (array) $this->message->getArguments();
    ksort($message_arguments);
    ksort($this->arguments);
    if ($this->arguments !== $message_arguments) {
      $message_needs_save = TRUE;
      // Arguments set with ::addArguments() are taking precedence.
      $arguments = $this->arguments + $message_arguments;
      $this->message->setArguments($arguments);
    }

    // If the arguments were altered or message is not saved, do this right now.
    if (!empty($message_needs_save) || $this->message->isNew()) {
      $this->message->save();
    }

    $mails = array_filter(array_map(function (UserInterface $account): ?string {
      // Anonymous accounts are filtered out.
      return !$account->isAnonymous() ? $account->getEmail() : NULL;
    }, $this->accounts));

    // Ensure uniqueness so that the message is not delivered multiple times to
    // the same address.
    $mails = array_unique($mails);

    // Send E-mail messages.
    return array_reduce($mails, function (bool $success, string $mail): bool {
      $options = ['save on success' => FALSE, 'mail' => $mail];
      return $success && $this->messageNotifier->send($this->message, $options);
    }, TRUE);
  }

}
