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
  public function addBccRecipients(array $bcc_emails): JoinupMessageDeliveryInterface {
    if (empty($this->message)) {
      throw new \Exception('The message has not been initialized yet.');
    }
    if (!$this->message->hasField('field_message_bcc')) {
      throw new \Exception('This message type does not support bcc. Please, add the field_message_bcc field to it.');
    }

    // Ensure that an empty list is not going to remove the previous entries.
    if (empty($bcc_emails)) {
      return $this;
    }

    $values = $this->message->get('field_message_bcc')->getValue();
    $values = array_map(function (array $value): string {
      return $value['value'];
    }, $values);

    $final = array_unique(array_merge($values, $bcc_emails));
    $this->message->set('field_message_bcc', $final);
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

    // Merge E-mail addresses extracted from passed from user accounts with
    // those passed directly as recipient E-mail addresses. Ensure uniqueness.
    $mails = array_unique(array_merge($mails, $this->mails));

    // Send E-mail messages.
    return array_reduce($mails, function (bool $success, string $mail): bool {
      $options = ['save on success' => FALSE, 'mail' => $mail];
      return $success && $this->messageNotifier->send($this->message, $options);
    }, TRUE);
  }

}
