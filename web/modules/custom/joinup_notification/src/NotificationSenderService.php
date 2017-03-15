<?php

namespace Drupal\joinup_notification;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\joinup_core\JoinupRelationManager;
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
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\message_notify\MessageNotifier definition.
   *
   * @var \Drupal\message_notify\MessageNotifier
   */
  protected $messageNotifySender;

  /**
   * The discussions relation manager.
   *
   * @var \Drupal\joinup_core\JoinupRelationManager
   */
  protected $relationManager;

  /**
   * Constructs the event object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\message_notify\MessageNotifier $message_notify_sender
   *   The message notify sender service.
   * @param \Drupal\joinup_core\JoinupRelationManager $relation_manager
   *   The relation manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MessageNotifier $message_notify_sender, JoinupRelationManager $relation_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->messageNotifySender = $message_notify_sender;
    $this->relationManager = $relation_manager;
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
      $user_ids = $this->entityTypeManager->getStorage('user')->getQuery()
        ->condition('status', 1)
        ->condition('roles', $role_id)
        ->execute();
      $recipients = $user_ids;
    }
    else {
      $membership_query = $this->entityTypeManager->getStorage('og_membership')->getQuery()
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
