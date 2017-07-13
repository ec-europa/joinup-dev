<?php

namespace Drupal\contact_form\EventSubscriber;

use Drupal\contact_form\ContactFormEvents;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\joinup_notification\Event\NotificationEvent;
use Drupal\message_notify\MessageNotifier;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class responsible to send the email after a contact form.
 */
class NotificationSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The message notifier service.
   *
   * @var \Drupal\message_notify\MessageNotifier
   */
  protected $messageNotifier;

  /**
   * Constructs a new CommunityContentSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\message_notify\MessageNotifier $message_notifier
   *   The message notifier service.
   */
  public function __construct(EntityTypeManager $entity_type_manager, MessageNotifier $message_notifier) {
    $this->entityTypeManager = $entity_type_manager;
    $this->messageNotifier = $message_notifier;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ContactFormEvents::CONTACT_FORM_EVENT] = ['contactForm'];
    return $events;
  }

  /**
   * The callback method for the contact_form.notify event.
   *
   * @param \Drupal\joinup_notification\Event\NotificationEvent $event
   *   The event object.
   */
  public function contactForm(NotificationEvent $event) {
    if ($event->getOperation() !== 'contact') {
      return;
    }

    $user_ids = $this->getRecipientIdsByRole('moderator');
    /** @var \Drupal\message\MessageInterface $message */
    $message = $event->getEntity();
    $message->save();

    foreach ($user_ids as $user_id) {
      /** @var \Drupal\user\Entity\User $user */
      $user = $this->entityTypeManager->getStorage('user')->load($user_id);
      if ($user->isAnonymous()) {
        continue;
      }
      $options = ['save on success' => FALSE, 'mail' => $user->getEmail()];
      $this->messageNotifier->send($message, $options);
    }
  }

  /**
   * Returns the users with a given role.
   *
   * @param string $role_id
   *   The role id.
   *
   * @return array
   *   An array of user ids.
   */
  protected function getRecipientIdsByRole($role_id) {
    return $this->entityTypeManager->getStorage('user')->getQuery()
      ->condition('status', 1)
      ->condition('roles', $role_id)
      ->execute();
  }

}
