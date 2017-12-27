<?php

declare(strict_types = 1);

namespace Drupal\joinup_notification;

use Drupal\message\MessageInterface;

/**
 * Interface for the message delivery service.
 *
 * Note: A message passed or created with this service will not vary on
 * recipient as the arguments are the same for all passed recipients. As an
 * effect, recipient-based customisation of messages is not possible (i.e. it
 * isn't possible to use such recipient tokens/arguments:
 * @code
 * Attn: @recipient:first_name @recipient:last_name
 * @endcode
 * This is because passed arguments are message-based. The reason behind this
 * behaviour is that we don't want to flood the system with messages, we create
 * only a message for a specific event. However, messages sent using this
 * service will be automatically prefixed with the recipient full name, as you
 * can see in joinup_notification_mail_alter().
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
   * @param bool $digest
   *   Whether or not to include the message in the user's periodic notification
   *   digest. If set to TRUE the message will be sent according to the users'
   *   chosen notification frequency: immediately, daily, weekly or monthly. If
   *   set to FALSE the message will be sent immediately. Defaults to FALSE.
   *
   * @return bool
   *   Whether or not the messages were sent successfully.
   */
  public function sendMessageToUsers(MessageInterface $message, array $accounts, bool $digest = FALSE): bool;

  /**
   * Sends the given Message entity to the given e-mail addresses.
   *
   * If the message entity is not saved, the service will take care to save it
   * prior to delivery.
   *
   * This is intended to be used only when sending messages to recipients which
   * are not registered users of the website. If the recipient is a registered
   * user, use ::sendMessageToUsers() instead, which honors the user's chosen
   * message delivery frequency.
   *
   * @param \Drupal\message\MessageInterface $message
   *   The message to be delivered.
   * @param string[] $mails
   *   A list of e-mail addresses to send the message to.
   *
   * @return bool
   *   Whether or not the messages were sent successfully.
   */
  public function sendMessageToEmailAddresses(MessageInterface $message, array $mails): bool;

  /**
   * Creates the message entity to be delivered.
   *
   * If there's no message entity available when using the service, the service
   * can create the message entity by passing the message template. Additional
   * message entity values can be passed in the second parameter. In the case
   * when the message entity is available before using this service, just use
   * the ::setMessage() method to pass the entity.
   *
   * @param string $message_template
   *   The message template ID.
   * @param array $values
   *   (optional) Additional values to be passed to the message entity creation,
   *   similar to values passed to EntityInterface::create().
   *
   * @return $this
   *
   * @see \Drupal\Core\Entity\EntityInterface::create()
   * @see self::setMessage()
   */
  public function createMessage(string $message_template, array $values = []): self;

  /**
   * Sets arguments to the message entity, overriding existing ones.
   *
   * If a message entity has been already set, before calling this method, and
   * the message entity has arguments, those arguments will be overwritten by
   * the ones provided to this method. Also, if these arguments are changing an
   * existing, saved entity, the message entity will be resaved.
   *
   * @param array $arguments
   *   The arguments to be passed to the message entity.
   *
   * @return $this
   */
  public function setArguments(array $arguments): self;

  /**
   * Sets the list of message recipients.
   *
   * Alternatively or complementary, ::setRecipientsAsEmails() can be used in
   * order to pass directly a list of E-mail addresses.
   *
   * @param \Drupal\user\UserInterface[] $accounts
   *   A list of user accounts as recipients.
   *
   * @return $this
   *
   * @see self::setRecipientsAsEmails()
   */
  public function setRecipients(array $accounts): self;

  /**
   * Sends the message to the recipients.
   *
   * @return bool
   *   Whether or not the messages were sent successfully.
   */
  public function sendMail(): bool;

}
