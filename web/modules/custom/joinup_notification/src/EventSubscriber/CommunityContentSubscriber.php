<?php

declare(strict_types = 1);

namespace Drupal\joinup_notification\EventSubscriber;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\joinup_group\JoinupGroupHelper;
use Drupal\joinup_notification\Event\NotificationEvent;
use Drupal\joinup_notification\JoinupMessageDeliveryInterface;
use Drupal\joinup_notification\MessageArgumentGenerator;
use Drupal\joinup_notification\NotificationEvents;
use Drupal\joinup_workflow\WorkflowHelperInterface;
use Drupal\og\GroupTypeManager;
use Drupal\og\MembershipManager;
use Drupal\og\OgRoleInterface;
use Drupal\state_machine_revisions\RevisionManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles notifications related to community content.
 */
class CommunityContentSubscriber extends NotificationSubscriberBase implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The transition object.
   *
   * @var \Drupal\state_machine\Plugin\Workflow\WorkflowTransition
   */
  protected $transition;

  /**
   * The workflow object.
   *
   * @var \Drupal\state_machine\Plugin\Workflow\Workflow
   */
  protected $workflow;

  /**
   * The state field name of the entity object.
   *
   * @var string
   */
  protected $stateField;

  /**
   * The motivation text passed in the entity.
   *
   * @var string
   */
  protected $motivation;

  /**
   * Whether the community content has a published version.
   *
   * @var bool
   */
  protected $hasPublished;

  /**
   * The revision manager service.
   *
   * @var \Drupal\state_machine_revisions\RevisionManagerInterface
   */
  protected $revisionManager;

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
   * @param \Drupal\joinup_workflow\WorkflowHelperInterface $workflow_helper
   *   The workflow helper service.
   * @param \Drupal\joinup_notification\JoinupMessageDeliveryInterface $message_delivery
   *   The message deliver service.
   * @param \Drupal\state_machine_revisions\RevisionManagerInterface $revision_manager
   *   The revision manager service.
   */
  public function __construct(EntityTypeManager $entity_type_manager, ConfigFactory $config_factory, AccountProxy $current_user, GroupTypeManager $og_group_type_manager, MembershipManager $og_membership_manager, WorkflowHelperInterface $workflow_helper, JoinupMessageDeliveryInterface $message_delivery, RevisionManagerInterface $revision_manager) {
    parent::__construct($entity_type_manager, $config_factory, $current_user, $og_group_type_manager, $og_membership_manager, $workflow_helper, $message_delivery);
    $this->revisionManager = $revision_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      NotificationEvents::COMMUNITY_CONTENT_CREATE => ['onCreate'],
      NotificationEvents::COMMUNITY_CONTENT_UPDATE => ['onUpdate'],
      NotificationEvents::COMMUNITY_CONTENT_DELETE => ['onDelete'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function initialize(NotificationEvent $event) {
    parent::initialize($event);

    $state_item = $this->workflowHelper->getEntityStateFieldDefinition($this->entity->getEntityTypeId(), $this->entity->bundle());
    if (!empty($state_item)) {
      $this->stateField = $state_item->getName();
      $from_state = isset($this->entity->field_state_initial_value) ? $this->entity->field_state_initial_value : 'draft';
      $to_state = $this->entity->get($this->stateField)->first()->value;

      $this->workflow = $this->entity->get($this->stateField)->first()->getWorkflow();
      // In some cases the workflow cannot be determined, for example when
      // deleting orphaned group content that has a workflow that depends on the
      // parent entity's content moderation status.
      if ($this->workflow) {
        $this->transition = $this->workflow->findTransition($from_state, $to_state);
      }
    }
    $this->motivation = empty($this->entity->motivation) ? '' : $this->entity->motivation;
    $this->hasPublished = $this->hasPublishedVersion($this->entity);
  }

  /**
   * Sends notifications on a create operation.
   *
   * @param \Drupal\joinup_notification\Event\NotificationEvent $event
   *   The event object.
   */
  public function onCreate(NotificationEvent $event) {
    $this->initialize($event);
    if (!$this->appliesOnCreate()) {
      return;
    }

    if (empty($this->config[$this->workflow->getId()][$this->transition->getId()])) {
      return;
    }

    $user_data = $this->getUsersMessages($this->config[$this->workflow->getId()][$this->transition->getId()]);
    $this->sendUserDataMessages($user_data);
  }

  /**
   * Checks if the event applies for the create operation.
   *
   * @return bool
   *   Whether the event applies.
   */
  protected function appliesOnCreate() {
    // If there is no original version, then it is not an update.
    if (isset($this->entity->original)) {
      return FALSE;
    }

    // If any of the workflow related properties are empty, return early.
    if (empty($this->stateField) || empty($this->workflow) || empty($this->transition)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Sends notifications on an update operation.
   *
   * @param \Drupal\joinup_notification\Event\NotificationEvent $event
   *   The event object.
   */
  public function onUpdate(NotificationEvent $event) {
    $this->initialize($event);
    if (!$this->appliesOnUpdate()) {
      return;
    }

    if (empty($this->config[$this->workflow->getId()][$this->transition->getId()])) {
      return;
    }

    $user_data = $this->getUsersMessages($this->config[$this->workflow->getId()][$this->transition->getId()]);
    $this->sendUserDataMessages($user_data);
  }

  /**
   * Checks if the event applies for the update operation.
   *
   * @return bool
   *   Whether the event applies.
   */
  protected function appliesOnUpdate() {
    // If there is no original version, then it is not an update.
    if ($this->entity->isNew()) {
      return FALSE;
    }

    // If any of the workflow related properties are empty, return early.
    if (empty($this->stateField) || empty($this->workflow) || empty($this->transition)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Sends notifications on an delete operation.
   *
   * @param \Drupal\joinup_notification\Event\NotificationEvent $event
   *   The event object.
   */
  public function onDelete(NotificationEvent $event) {
    $this->initialize($event);
    if (!$this->appliesOnDelete()) {
      return;
    }

    // The storage class passes the loaded entity to the hooks when a delete
    // operation occurs. This returns the wrong state of the entity so the
    // latest revision is forced here.
    if ($latest_revision = $this->revisionManager->loadLatestRevision($this->entity)) {
      $state = $latest_revision->get($this->stateField)->first()->value;
      if (empty($this->workflow) || empty($this->config[$this->workflow->getId()][$state])) {
        return;
      }

      $transition_action = $state === 'deletion_request' ? $this->t('approved your request of deletion for') : $this->t('deleted');
      $user_data = $this->getUsersMessages($this->config[$this->workflow->getId()][$state]);
      $arguments = ['@transition:request_action:past' => $transition_action];
      $this->sendUserDataMessages($user_data, $arguments);
    }
  }

  /**
   * Checks if the event applies for the delete operation.
   *
   * @return bool
   *   Whether the event applies.
   */
  protected function appliesOnDelete() {
    // If any of the workflow related properties are empty, return early.
    if (empty($this->stateField)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigurationName() {
    return 'joinup_notification.notifications.community_content';
  }

  /**
   * {@inheritdoc}
   */
  protected function generateArguments(EntityInterface $entity): array {
    $arguments = parent::generateArguments($entity);
    /** @var \Drupal\user\UserInterface $actor */
    $actor = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    $motivation = isset($this->entity->motivation) ? $this->entity->motivation : '';

    $arguments['@actor:full_name'] = $actor->getDisplayName();
    $arguments['@transition:motivation'] = $motivation;
    $arguments['@entity:hasPublished:status'] = $this->hasPublished ? 'an update of the' : 'a new';

    // Add arguments related to the parent collection or solution.
    $parent = JoinupGroupHelper::getGroup($entity);
    if (!empty($parent)) {
      $arguments += MessageArgumentGenerator::getGroupArguments($parent);

      // If the role is not yet set, get it from the parent collection|solution.
      if (empty($arguments['@actor:role'])) {
        $membership = $this->membershipManager->getMembership($parent, $actor->id());
        if (!empty($membership)) {
          $role_names = array_map(function (OgRoleInterface $og_role) {
            return $og_role->getName();
          }, $membership->getRoles());

          if (in_array('administrator', $role_names)) {
            $arguments['@actor:role'] = $this->t('Owner');
          }
          elseif (in_array('facilitator', $role_names)) {
            $arguments['@actor:role'] = $this->t('Facilitator');
          }
        }
      }
    }

    return $arguments;
  }

  /**
   * Checks whether the entity has a published version.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return bool
   *   Whether the entity has a published version.
   */
  protected  function hasPublishedVersion(EntityInterface $entity) {
    if ($entity->isNew()) {
      return FALSE;
    }
    if ($entity->isPublished()) {
      return TRUE;
    }
    $published = $this->entityTypeManager->getStorage('node')->load($entity->id());
    return !empty($published) && $published->isPublished();
  }

}
