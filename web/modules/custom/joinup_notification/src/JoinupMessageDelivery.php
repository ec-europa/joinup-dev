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
   * E-mail recipients.
   *
   * @var string[]
   */
  protected $mails = [];

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
  protected $arguments;

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
  public function setMessage(MessageInterface $message): JoinupMessageDeliveryInterface {
    $this->message = $message;
    return $this;
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
  public function setRecipientsAsEmails(array $mails): JoinupMessageDeliveryInterface {
    $this->mails = $mails;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function sendMail(): JoinupMessageDeliveryInterface {
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

    // Merge E-mail addresses extracted from passed from user accounts with
    // those passed directly as recipient E-mail addresses. Ensure uniqueness.
    $mails = array_unique(array_merge($mails, $this->mails));

    // Send E-mail messages.
    array_walk($mails, function (string $mail): void {
      $options = ['save on success' => FALSE, 'mail' => $mail];
      $this->messageNotifier->send($this->message, $options);
    });

    return $this;
  }

}
