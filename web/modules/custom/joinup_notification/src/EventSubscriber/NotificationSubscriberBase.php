<?php

declare(strict_types = 1);

namespace Drupal\joinup_notification\EventSubscriber;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\joinup_group\JoinupGroupHelper;
use Drupal\joinup_notification\Event\NotificationEvent;
use Drupal\joinup_notification\JoinupMessageDeliveryInterface;
use Drupal\joinup_notification\MessageArgumentGenerator;
use Drupal\joinup_workflow\WorkflowHelperInterface;
use Drupal\og\GroupTypeManager;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgRoleInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * A base class for the notification subscribers.
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
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * The workflow helper service.
   *
   * @var \Drupal\joinup_workflow\WorkflowHelperInterface
   */
  protected $workflowHelper;

  /**
   * The message delivery service.
   *
   * @var \Drupal\joinup_notification\JoinupMessageDeliveryInterface
   */
  protected $messageDelivery;

  /**
   * Constructs a new CommunityContentSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user service.
   * @param \Drupal\og\GroupTypeManager $og_group_type_manager
   *   The og group type manager service.
   * @param \Drupal\og\MembershipManagerInterface $og_membership_manager
   *   The og membership manager service.
   * @param \Drupal\joinup_workflow\WorkflowHelperInterface $workflow_helper
   *   The workflow helper service.
   * @param \Drupal\joinup_notification\JoinupMessageDeliveryInterface $message_delivery
   *   The message delivery service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactory $config_factory, AccountProxy $current_user, GroupTypeManager $og_group_type_manager, MembershipManagerInterface $og_membership_manager, WorkflowHelperInterface $workflow_helper, JoinupMessageDeliveryInterface $message_delivery) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->groupTypeManager = $og_group_type_manager;
    $this->membershipManager = $og_membership_manager;
    $this->workflowHelper = $workflow_helper;
    $this->messageDelivery = $message_delivery;
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
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   Optionally alter the entity to be checked.
   *
   * @return array
   *   An array of message ids that every key is an array of user ids.
   */
  protected function getUsersMessages(array $user_data, ?EntityInterface $entity = NULL) {
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

    $og_role_storage = $this->entityTypeManager->getStorage('og_role');
    foreach ($user_data['og_roles'] as $role_id => $messages) {
      $role = $og_role_storage->load($role_id);
      $recipients = $this->getRecipientIdsByOgRole($entity, $role);
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
   * Retrieves a list of emails from a given list of roles.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group entity.
   * @param array $bcc_data
   *   An associative array of roles indexed by the 'roles' key or the
   *   'og_roles' key depending on whether it is a site wide role or an og role.
   * @param int[] $uids_to_skip
   *   (optional) An array of ids to skip.
   *
   * @return string[]
   *   An array of emails.
   */
  protected function getBccEmails(EntityInterface $entity, array $bcc_data, array $uids_to_skip = []): array {
    $user_storage = $this->entityTypeManager->getStorage('user');
    $return = [];
    foreach ($bcc_data['roles'] as $role_id) {
      $uids = $this->getRecipientIdsByRole($role_id);
      if ($uids = array_diff(array_values($uids), $uids_to_skip)) {
        $emails = array_map(function (UserInterface $user): string {
          return $user->getEmail();
        }, $user_storage->loadMultiple($uids));
        $return += $emails;
      }
    }

    if (isset($bcc_data['og_roles'])) {
      $og_role_storage = $this->entityTypeManager->getStorage('og_role');
      /** @var \Drupal\og\OgRoleInterface[] $roles */
      $roles = $og_role_storage->loadMultiple(array_keys($bcc_data['og_roles']));
      foreach ($bcc_data['og_roles'] as $role_id => $messages) {
        $uids = $this->getRecipientIdsByOgRole($entity, $roles[$role_id]);
        if ($uids = array_diff(array_values($uids), $uids_to_skip)) {
          $emails = array_map(function (UserInterface $user): string {
            return $user->getEmail();
          }, $user_storage->loadMultiple($uids));
          $return += $emails;
        }
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
   * Returns the users with a given OG role.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param \Drupal\og\OgRoleInterface $role
   *   The role.
   *
   * @return array
   *   An array of user ids.
   */
  protected function getRecipientIdsByOgRole(EntityInterface $entity, OgRoleInterface $role): array {
    if (!$this->groupTypeManager->isGroup($entity->getEntityTypeId(), $entity->bundle())) {
      $entity = JoinupGroupHelper::getGroup($entity);
    }
    if (empty($entity)) {
      return [];
    }

    $memberships = $this->membershipManager->getGroupMembershipsByRoleNames($entity, [$role->getName()]);

    // We need to handle possible broken relationships or memberships that
    // are not removed yet.
    $user_ids = array_map(function ($membership) {
      $user = $membership->getOwner();
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
   *   - Actor full name (This will be 'The Joinup Support Team' if the user
   *   has the moderator role)
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   *   Thrown when the URL for the entity cannot be generated.
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   Thrown when the first name or last name of the current user is not known.
   */
  protected function generateArguments(EntityInterface $entity): array {
    $arguments = [];

    $arguments['@entity:title'] = $entity->label();
    $arguments['@entity:bundle'] = $entity->bundle();
    $arguments['@entity:url'] = $entity->toUrl('canonical')->setAbsolute()->toString();
    $arguments['@user:my_subscriptions'] = Url::fromRoute('joinup_subscription.my_subscriptions')->setAbsolute()->toString();

    $arguments += MessageArgumentGenerator::getActorArguments();
    $arguments += MessageArgumentGenerator::getContactFormUrlArgument();

    $arguments['@site:legal_notice_url'] = Url::fromRoute('entity.entity_legal_document.canonical', ['entity_legal_document' => 'legal_notice'], ['absolute' => TRUE])->toString();

    return $arguments;
  }

  /**
   * Generates the arguments and calls for the sender service.
   *
   * @param array $user_data
   *   An array of user ids and their corresponding messages.
   * @param array $arguments
   *   (optional) Additional arguments to be passed to the message.
   * @param array $notifier_options
   *   An optional associative array of options to pass to the Email notifier
   *   plugin.
   * @param array $message_values
   *   Optional array of field values to send on the message entity.
   *
   * @return bool
   *   Whether or not the messages were sent successfully.
   */
  protected function sendUserDataMessages(array $user_data, array $arguments = [], array $notifier_options = [], array $message_values = []): bool {
    $arguments += $this->generateArguments($this->entity);

    $success = TRUE;
    foreach ($user_data as $template_id => $user_ids) {
      $success = $this->messageDelivery->sendMessageTemplateToMultipleUsers($template_id, $arguments, User::loadMultiple($user_ids), $notifier_options, $message_values) && $success;
    }
    return $success;
  }

  /**
   * Returns the configuration file that the subscriber will look into.
   *
   * For complex notifications it can be helpful to define data structures in a
   * YAML file which can then be used to make decisions about the notifications
   * to send.
   *
   * If a file is provided here it will be loaded during the initialization of
   * the event subscriber.
   *
   * @see \Drupal\joinup_notification\EventSubscriber\NotificationSubscriberBase::initialize()
   *
   * @return string
   *   The optional configuration file name.
   */
  protected function getConfigurationName() {
    return NULL;
  }

}
