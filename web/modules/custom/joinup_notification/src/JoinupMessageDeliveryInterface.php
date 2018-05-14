<?php

declare(strict_types = 1);

namespace Drupal\joinup_notification;

use Drupal\message\MessageInterface;
use Drupal\user\UserInterface;

/**
 * Interface for the message delivery service.
 *
 * Note: A message passed or created using the methods that have multiple
 * recipients will not set any of the recipients as the owner of the message.
 * Instead it will create one single Message entity and send it repeatedly using
 * the Message Notify module.
 * The reason behind this behaviour is that we don't want to flood the system
 * with messages, we create only a single message for a specific event.
 * There are some drawbacks with this approach though: bundling messages in a
 * digest is not possible, and all sent messages will have identical arguments
 * for all passed recipients. As an effect, recipient-based customisation of
 * messages is not possible (i.e. it isn't possible to use such recipient
 * tokens/arguments:
 * @code
 * Attn: @recipient:first_name @recipient:last_name
 * @endcode
 * However, messages sent using this service will be automatically prefixed with
 * the recipient full name, as you can see in joinup_notification_mail_alter().
 *
 * If you need per-user arguments or need to be able to bundle messages in a
 * digest, use the methods that send a message to a single user. These will set
 * the recipient as the owner of the Message entity in the standard way.
 *
 * @see joinup_notification_mail_alter()
 */
interface JoinupMessageDeliveryInterface {

  /**
   * Sends the given Message entity to the given users.
   *
   * If the message entity is not saved, the service will take care to save it
   * prior to delivery.
   *
   * @param \Drupal\message\MessageInterface $message
   *   The message to be delivered.
   * @param \Drupal\user\UserInterface[] $accounts
   *   A list of user accounts as recipients.
   * @param array $notifier_options
   *   An optional associative array of options to pass to the Email notifier
   *   plugin.
   *
   * @return bool
   *   Whether or not the messages were sent successfully.
   *
   * @throws \LogicException
   *   Thrown when a message is attempted to be sent to a user which doesn't
   *   have an e-mail address.
   */
  public function sendMessageToMultipleUsers(MessageInterface $message, array $accounts, array $notifier_options = []): bool;

  /**
   * Sends the given Message entity to the given e-mail addresses.
   *
   * If the message entity is not saved, the service will take care to save it
   * prior to delivery.
   *
   * This is intended to be used only when sending messages to recipients which
   * are not registered users of the website. If the recipient is a registered
   * user, use ::sendMessageToMultipleUsers() instead.
   *
   * @param \Drupal\message\MessageInterface $message
   *   The message to be delivered.
   * @param string[] $mails
   *   A list of e-mail addresses to send the message to.
   * @param array $notifier_options
   *   An optional associative array of options to pass to the Email notifier
   *   plugin.
   *
   * @return bool
   *   Whether or not the messages were sent successfully.
   */
  public function sendMessageToEmailAddresses(MessageInterface $message, array $mails, array $notifier_options = []): bool;

  /**
   * Sends a Message based on the given message template to the given user.
   *
   * @param string $message_template
   *   The message template ID.
   * @param array $arguments
   *   The arguments to be set on the message entity.
   * @param \Drupal\user\UserInterface $account
   *   A user account that will be the recipient of the message.
   * @param array $notifier_options
   *   An optional associative array of options to pass to the notifier plugin
   *   that is used (either Email or Digest).
   * @param bool $digest
   *   Whether or not to include the message in the user's periodic notification
   *   digest. If set to TRUE the message will be sent according to the user's
   *   chosen notification frequency: immediately, daily, weekly or monthly. If
   *   set to FALSE the message will be sent immediately. Defaults to TRUE.
   *
   * @return bool
   *   Whether or not the message was sent successfully.
   *
   * @throws \LogicException
   *   Thrown when a message is attempted to be sent to a user which doesn't
   *   have an e-mail address.
   */
  public function sendMessageTemplateToUser(string $message_template, array $arguments, UserInterface $account, array $notifier_options = [], bool $digest = TRUE): bool;

  /**
   * Sends a Message based on the given message template to multiple users.
   *
   * This will create a single Message entity and resend it multiple times to
   * different users using the Email notifier plugin from Message Notify.
   *
   * @param string $message_template
   *   The message template ID.
   * @param array $arguments
   *   The arguments to be set on the message entity.
   * @param \Drupal\user\UserInterface[] $accounts
   *   A list of user accounts as recipients.
   * @param array $notifier_options
   *   An optional associative array of options to pass to the Email notifier
   *   plugin.
   *
   * @return bool
   *   Whether or not the messages were sent successfully.
   */
  public function sendMessageTemplateToMultipleUsers(string $message_template, array $arguments, array $accounts, array $notifier_options = []): bool;

  /**
   * Sends a Message based on the given message template to the given addresses.
   *
   * This is intended to be used only when sending messages to recipients which
   * are not registered users of the website. If the recipient is a registered
   * user, use ::sendMessageTemplateToMultipleUsers() instead.
   *
   * @param string $message_template
   *   The message template ID.
   * @param array $arguments
   *   The arguments to be set on the message entity.
   * @param string[] $mails
   *   A list of e-mail addresses to send the message to.
   * @param array $notifier_options
   *   An optional associative array of options to pass to the Email notifier
   *   plugin.
   *
   * @return bool
   *   Whether or not the messages were sent successfully.
   */
  public function sendMessageTemplateToEmailAddresses(string $message_template, array $arguments, array $mails, array $notifier_options = []): bool;

}
