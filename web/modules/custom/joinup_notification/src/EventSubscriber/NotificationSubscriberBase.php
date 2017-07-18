<?php

namespace Drupal\joinup_notification\EventSubscriber;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Session\AccountProxy;
use Drupal\joinup_core\JoinupRelationManager;
use Drupal\joinup_core\WorkflowHelper;
use Drupal\joinup_notification\Event\NotificationEvent;
use Drupal\message\Entity\Message;
use Drupal\message_notify\MessageNotifier;
use Drupal\og\GroupTypeManager;
use Drupal\og\MembershipManager;
use Drupal\og\OgMembershipInterface;

/**
 * A base class for the notification subscribers.
 *
 * @package Drupal\joinup_notification\EventSubscriber
 */
abstract class NotificationSubscriberBase {

  /**
   * The entity object.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The operation id.
   *
   * @var string
   */
  protected $operation;

  /**
   * The config object that has the notification settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The group type manager.
   *
   * @var \Drupal\og\GroupTypeManager
   */
  protected $groupTypeManager;

  /**
   * The membership manager service.
   *
   * @var \Drupal\og\MembershipManager
   */
  protected $membershipManager;

  /**
   * The workflow helper service.
   *
   * @var \Drupal\joinup_core\WorkflowHelper
   */
  protected $workflowHelper;

  /**
   * The relation manager service.
   *
   * @var \Drupal\joinup_core\JoinupRelationManager
   */
  protected $relationManager;

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
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user service.
   * @param \Drupal\og\GroupTypeManager $og_group_type_manager
   *   The og group type manager service.
   * @param \Drupal\og\MembershipManager $og_membership_manager
   *   The og membership manager service.
   * @param \Drupal\joinup_core\WorkflowHelper $joinup_core_workflow_helper
   *   The workflow helper service.
   * @param \Drupal\joinup_core\JoinupRelationManager $joinup_core_relations_manager
   *   The relation manager service.
   * @param \Drupal\message_notify\MessageNotifier $message_notifier
   *   The message notifier service.
   */
  public function __construct(EntityTypeManager $entity_type_manager, ConfigFactory $config_factory, AccountProxy $current_user, GroupTypeManager $og_group_type_manager, MembershipManager $og_membership_manager, WorkflowHelper $joinup_core_workflow_helper, JoinupRelationManager $joinup_core_relations_manager, MessageNotifier $message_notifier) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->groupTypeManager = $og_group_type_manager;
    $this->membershipManager = $og_membership_manager;
    $this->workflowHelper = $joinup_core_workflow_helper;
    $this->relationManager = $joinup_core_relations_manager;
    $this->messageNotifier = $message_notifier;
  }

  /**
   * Initializes the necessary data to be shared by the methods.
   *
   * @param \Drupal\joinup_notification\Event\NotificationEvent $event
   *   The event object.
   *
   * @throws \Exception
   *    Thrown if the configuration file is not loaded.
   */
  protected function initialize(NotificationEvent $event) {
    $this->entity = $event->getEntity();
    $this->operation = $event->getOperation();
    $this->config = $this->configFactory->get($this->getConfigurationName())->get($this->operation);
  }

  /**
   * Converts the user data array to an array of user ids and messages.
   *
   * @param array $user_data
   *   A structured array of user ownership and roles and their corresponding
   *   message ids.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Optionally alter the entity to be checked.
   *
   * @return array
   *   An array of user ids that every key is an array of message ids.
   */
  protected function getUsersMessages(array $user_data, EntityInterface $entity = NULL) {
    $entity = $entity ?: $this->entity;
    // Ensure proper loops.
    $user_data += [
      'roles' => [],
      'og_roles' => [],
    ];
    // By default, skip the anonymous user and the actor.
    // The actor might be an anonymous user.
    $uids_to_skip = array_unique([0, $this->currentUser->id()]);
    $message_data = [];

    if (!empty($user_data['owner']) && $entity->getOwnerId() !== $this->currentUser->id() && !$entity->getOwner()->isAnonymous()) {
      $message_data[$entity->getOwnerId()] = $user_data['owner'];
      $uids_to_skip[] = $entity->getOwnerId();
    }

    foreach ($user_data['roles'] as $role_id => $messages) {
      $recipients = $this->getRecipientIdsByRole($role_id);
      $recipients = array_diff(array_values($recipients), $uids_to_skip);
      foreach ($recipients as $uid) {
        $message_data[$uid] = $messages;
      }
    }

    foreach ($user_data['og_roles'] as $role_id => $messages) {
      $recipients = $this->getRecipientIdsByOgRole($entity, $role_id);
      $recipients = array_diff(array_values($recipients), $uids_to_skip);
      foreach ($recipients as $uid) {
        $message_data[$uid] = $messages;
      }
    }

    // Flip the array to have the user ids grouped by the message id.
    $return = [];
    foreach ($message_data as $user_id => $message_ids) {
      foreach ($message_ids as $message_id) {
        $return[$message_id][$user_id] = $user_id;
      }
    }

    return $return;
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
      'entity_type' => $entity->getEntityTypeId(),
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
    return array_values(array_filter($user_ids));
  }

  /**
   * Generates a list of arguments to be passed to the message entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The $entity object.
   *
   * @return array
   *   An associative array with the arguments as keys and their replacements
   *    as values.
   *
   *   Default generated arguments are:
   *   - Entity title
   *   - Entity bundle
   *   - Entity url
   *   - Actor first name
   *   - Actor family name
   *   - Actor role
   *   - Actor full name (This will be 'the Joinup Moderation Team' if the user
   *   has the moderator role)
   */
  protected function generateArguments(EntityInterface $entity) {
    $arguments = [];
    $actor = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    $actor_first_name = !empty($actor->get('field_user_first_name')->first()->value) ? $actor->get('field_user_first_name')->first()->value : '';
    $actor_family_name = !empty($actor->get('field_user_family_name')->first()->value) ? $actor->get('field_user_family_name')->first()->value : '';

    $arguments['@entity:title'] = $entity->label();
    $arguments['@entity:bundle'] = $entity->bundle();
    $arguments['@entity:url'] = $entity->toUrl('canonical', ['absolute' => TRUE])->toString();
    $arguments['@actor:field_user_first_name'] = $actor_first_name;
    $arguments['@actor:field_user_family_name'] = $actor_family_name;

    if ($actor->hasRole('moderator')) {
      /** @var \Drupal\user\RoleInterface $role */
      $role = $this->entityTypeManager->getStorage('user_role')->load('moderator');
      $arguments['@actor:role'] = $role->label();
      $arguments['@actor:full_name'] = 'the Joinup Moderation Team';
    }

    return $arguments;
  }

  /**
   * Generates the arguments and calls for the sender service.
   *
   * @param array $user_data
   *   An array of user ids and their corresponding messages.
   */
  protected function sendUserDataMessages(array $user_data) {
    $arguments = $this->generateArguments($this->entity);

    foreach ($user_data as $template_id => $user_ids) {
      $values = ['template' => $template_id, 'arguments' => $arguments];
      $message = Message::create($values);
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
  }

  /**
   * Returns the configuration file that the subscriber will look into.
   *
   * @return string
   *   The configuration file name.
   */
  abstract protected function getConfigurationName();

}
