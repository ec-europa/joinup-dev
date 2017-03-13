<?php

namespace Drupal\joinup_notification;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\message\Entity\Message;
use Drupal\message_notify\MessageNotifier;
use Drupal\og\Entity\OgMembership;
use Drupal\user\Entity\Role;

/**
 * Class NotificationSenderService.
 *
 * @package Drupal\joinup_notification
 */
class NotificationSenderService {

  /**
   * Drupal\Core\Entity\EntityManager definition.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Drupal\message_notify\MessageNotifier definition.
   *
   * @var \Drupal\message_notify\MessageNotifier
   */
  protected $messageNotifySender;

  /**
   * Constructs the event object.
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager service.
   * @param \Drupal\message_notify\MessageNotifier $message_notify_sender
   *   The message notify sender service.
   */
  public function __construct(EntityManager $entity_manager, MessageNotifier $message_notify_sender) {
    $this->entityManager = $entity_manager;
    $this->messageNotifySender = $message_notify_sender;
  }

  /**
   * Sends notifications to users with the passed role.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The state change event.
   * @param string $role_id
   *   The id of the role. The role can be site-wide or organic group.
   * @param array $message_ids
   *   An array of Message template ids.
   *
   * @throws \Drupal\message_notify\Exception\MessageNotifyException
   *
   * @see modules/custom/joinup_notification/src/config/schema/joinup_notification.schema.yml
   */
  public function send(EntityInterface $entity, $role_id, array $message_ids) {
    $role = Role::load($role_id);
    if (!empty($role)) {
      $user_ids = \Drupal::service('entity.manager')->getStorage('user')->getQuery()
        ->condition('status', 1)
        ->condition('roles', $role_id)
        ->execute();
      $recipients = $user_ids;
    }
    else {
      $membership_query = \Drupal::service('entity.manager')->getStorage('og_membership')->getQuery()
        ->condition('state', 'active')
        ->condition('entity_id', $entity->id());
      $memberships_ids = $membership_query->execute();
      $memberships = OgMembership::loadMultiple($memberships_ids);
      $memberships = array_filter($memberships, function ($membership) use ($role_id) {
        $role_ids = array_map(function ($og_role) {
          return $og_role->id();
        }, $membership->getRoles());
        return in_array($role_id, $role_ids);
      });
      // We need to handle possible broken relationships or memberships that
      // are not removed yet.
      $user_ids = array_map(function ($membership) {
        $user = $membership->getUser();
        return empty($user) ? NULL : $user->id();
      }, $memberships);
      $recipients = array_filter($user_ids);
    }

    /** @var \Drupal\og\Entity\OgMembership $membership */
    foreach ($recipients as $user_id) {
      foreach ($message_ids as $message_id) {
        // Create the actual message and save it to the db.
        $message = Message::create([
          'template' => $message_id,
          'uid' => $user_id,
          'field_message_content' => $entity->id(),
        ]);
        $message->save();
        // Send the saved message as an e-mail.
        $this->messageNotifySender->send($message, [], 'email');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $keys = [
      'solution.validate.post_transition',
      'solution.request_deletion.post_transition',
    ];

    foreach ($keys as $key) {
      $events[$key][] = ['messageSender'];
    }

    return $events;
  }

}
