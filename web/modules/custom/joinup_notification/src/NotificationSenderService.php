<?php

namespace Drupal\joinup_notification;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\joinup_core\JoinupRelationManager;
use Drupal\message\Entity\Message;
use Drupal\message_notify\MessageNotifier;
use Drupal\og\GroupTypeManager;
use Drupal\og\OgMembershipInterface;
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
   * The relation manager.
   *
   * @var \Drupal\joinup_core\JoinupRelationManager
   */
  protected $relationManager;

  /**
   * The OG group type manager.
   *
   * @var \Drupal\og\GroupTypeManager
   */
  protected $groupTypeManager;

  /**
   * Constructs the event object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\message_notify\MessageNotifier $message_notify_sender
   *   The message notify sender service.
   * @param \Drupal\joinup_core\JoinupRelationManager $relation_manager
   *   The relation manager service.
   * @param \Drupal\og\GroupTypeManager $group_type_manager
   *   The OG group type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MessageNotifier $message_notify_sender, JoinupRelationManager $relation_manager, GroupTypeManager $group_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->messageNotifySender = $message_notify_sender;
    $this->relationManager = $relation_manager;
    $this->groupTypeManager = $group_type_manager;
  }

  /**
   * Sends notifications about a state transition to users with the passed role.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The state change event.
   * @param string $role_id
   *   The id of the role. The role can be site-wide or organic group.
   * @param array $template_ids
   *   An array of Message template IDs.
   *
   * @throws \Drupal\message_notify\Exception\MessageNotifyException
   *
   * @see modules/custom/joinup_notification/src/config/schema/joinup_notification.schema.yml
   */
  public function sendStateTransitionMessage(EntityInterface $entity, $role_id, array $template_ids) {
    $role = Role::load($role_id);
    if (!empty($role)) {
      $recipient_ids = $this->getRecipientIdsByRole($role_id);
    }
    else {
      $recipient_ids = $this->getRecipientIdsByOgRole($entity, $role_id);
    }

    /** @var \Drupal\og\Entity\OgMembership $membership */
    foreach ($recipient_ids as $user_id) {
      foreach ($template_ids as $template_id) {
        // Create the actual message and save it to the db.
        $message = Message::create([
          'template' => $template_id,
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

  /**
   * Returns the users with a given og role.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param string $role_id
   *   The role id.
   *
   * @return array
   *   An array of user ids.
   */
  protected function getRecipientIdsByOgRole(EntityInterface $entity, $role_id) {
    if (!$this->groupTypeManager->isGroup($entity->getEntityTypeId(), $entity->bundle())) {
      $entity = $this->relationManager->getParent($entity);
    }
    if (empty($entity)) {
      return [];
    }

    $memberships = $this->entityTypeManager->getStorage('og_membership')->loadByProperties([
      'state' => OgMembershipInterface::STATE_ACTIVE,
      'entity_id' => $entity->id(),
    ]);

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
    return array_filter($user_ids);
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
