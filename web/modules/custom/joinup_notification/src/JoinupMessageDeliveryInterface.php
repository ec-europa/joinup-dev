<?php

declare(strict_types = 1);

namespace Drupal\joinup_notification;

use Drupal\message\MessageInterface;

/**
 * Interface for the message delivery service.
 *
 * This service allows easy sending of message entities as emails. If the
 * message entity is already available, the following snippet is sending the
 * message by E-mail:
 * @code
 * $message = Message::load(...);
 * $accounts = User::loadMultiple(...);
 * \Drupal::service('joinup.message_delivery')
 *   ->setMessage($message)
 *   // Some arguments can be overridden or new arguments can be added.
 *   ->addArguments(['@name' => 'Joe', '@gender' => 'M'])
 *   ->setRecipients($accounts)
 *   ->sendMail();
 * @endcode
 * Alternatively, when the message doesn't exist yet, the service knows how to
 * create it:@code
 * \Drupal::service('joinup.message_delivery')
 *   ->createMessage('discussion_invite', [
 *     'field_invitation' => $invitation_id,
 *   ])
 *   ->addArguments(['@name' => 'Joe', '@gender' => 'M'])
 *   ->setRecipients($accounts)
 *   // Add some additional E-mail addresses not belonging to user accounts.
 *   ->setRecipientsAsEmails(['abc@example.com', 'jane@example.com'])
 *   ->sendMail();
 * @endcode
 */
interface JoinupMessageDeliveryInterface {

  /**
   * Sets a message entity to be delivered.
   *
   * If the message entity is not saved, the service will take care to save it
   * prior to delivery. Alternatively, ::createMessage() method can be used in
   * order to allow the service handle the creation of the message entity. Use
   * this method if the message entity is already available when using this
   * service.
   *
   * @param \Drupal\message\MessageInterface $message
   *   The saved message to be delivered.
   *
   * @return $this
   *
   * @see self::createMessage()
   */
  public function setMessage(MessageInterface $message): self;

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
   * Sets recipients directly as E-mail addresses.
   *
   * This is an alternative but also complementary method of ::setRecipients().
   * Instead of passing a list of recipients as user accounts, you can pass
   * directly the E-mail addresses. Note that if both methods are used, the
   * E-mail addresses passed here will be appended to the list of the addresses
   * extracted from the user accounts passed in ::setRecipients().
   *
   * @param string[] $mails
   *   A list of recipient E-mail addresses.
   *
   * @return $this
   *
   * @see self::setRecipients()
   */
  public function setRecipientsAsEmails(array $mails): self;

  /**
   * Sends the message to the recipients.
   *
   * @return $this
   */
  public function sendMail(): self;

}
