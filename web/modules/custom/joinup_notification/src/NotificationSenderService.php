<?php

namespace Drupal\joinup_notification;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\joinup_core\JoinupRelationManager;
use Drupal\message\Entity\Message;
use Drupal\message\MessageInterface;
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
   * Sends a message to a list of recipients.
   *
   * @param \Drupal\message\MessageInterface $message
   *   The message to send.
   * @param array $recipient_ids
   *   A list of user IDs that should be notified via e-mail.
   */
  public function sendMessage(MessageInterface $message, array $recipient_ids) {
    foreach ($recipient_ids as $recipient_id) {
      $message->setOwnerId($recipient_id);
      $this->messageNotifySender->send($message, ['save_on_success' => FALSE]);
    }
  }

  /**
   * Creates a message with the given template and sends it to the recipients.
   *
   * @param string $template_id
   *   The template ID.
   * @param array $values
   *   The values to use in the template.
   * @param array $recipient_ids
   *   An array of user IDs to which the messages will be sent.
   */
  public function sendMessageTemplate($template_id, array $values, array $recipient_ids) {
    // Create the actual message and save it to the database.
    $values += ['template' => $template_id];
    $message = Message::create($values);
    $message->save();

    $this->sendMessage($message, $recipient_ids);
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
    if (isset($entity->skip_notification) && $entity->skip_notification === TRUE) {
      return;
    }
    $values = ['field_message_content' => $entity->id()];
    if ($this->groupTypeManager->isGroupContent($entity->getEntityTypeId(), $entity->bundle())) {
      $parent = $this->relationManager->getParent($entity);
      // If the field does not exist, the value will be simply skipped.
      $values += ['field_message_group' => $parent->id()];
    }
    $this->sendMessageTemplateToRole($template_ids, $values, $role_id, $entity);
  }

  /**
   * Sends a message template to a site-wide or group role.
   *
   * @param array $template_ids
   *   An array of Message template IDs.
   * @param array $values
   *   The values to pass in the message creation.
   * @param string $role_id
   *   The id of the role. The role can be site-wide or organic group.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The group or group content entity if the role is related to a group.
   */
  public function sendMessageTemplateToRole(array $template_ids, array $values, $role_id, EntityInterface $entity = NULL) {
    $role = Role::load($role_id);
    if (!empty($role)) {
      $recipient_ids = $this->getRecipientIdsByRole($role_id);
    }
    else {
      $recipient_ids = $this->getRecipientIdsByOgRole($entity, $role_id);
    }

    foreach ($template_ids as $template_id) {
      $this->sendMessageTemplate($template_id, $values, $recipient_ids);
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

}
