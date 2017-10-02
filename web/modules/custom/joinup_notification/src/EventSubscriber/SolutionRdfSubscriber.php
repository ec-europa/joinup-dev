<?php

namespace Drupal\joinup_notification\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\joinup_notification\Event\NotificationEvent;
use Drupal\joinup_notification\NotificationEvents;
use Drupal\og\OgRoleInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Notification handler for the notifications related to solutions.
 *
 * The notification base conditions are explained below.
 * @codingStandardsIgnoreStart
 * Template 1: sol_propose_new
 *   Operation: create
 *   Transition: propose
 *   hasPublished: false
 *   Recipients: moderator
 * Template 2: sol_approve_proposed
 *   Operation: update
 *   Transition: validate
 *   Source state: proposed
 *   Recipients: owner
 * Template 7: sol_propose_changes
 *   Operation: update
 *   Transition: propose
 *   Source state: validate
 *   Actor: Moderator
 *   Recipients: owner
 * Template 10: sol_request_deletion
 *   Operation: update
 *   Transition: request_deletion
 *   Recipients: moderator
 * Template 11: sol_deletion_approved
 *   Operation: delete
 *   Source state: deletion_request
 *   Recipients: owner
 * Template 12: sol_deletion_reject
 *   Operation: update
 *   Transition: validate
 *   Source state: deletion_request
 *   Recipients: owner
 * Template 13: sol_blacklist
 *   Operation: update
 *   Transition: blacklist
 *   Recipients: owner
 * Template 14: sol_publish_backlisted
 *   Operation: update
 *   Transition: validate
 *   Source state: blacklisted
 *   Recipients: owner
 * Template 15: sol_request_changes
 *   Operation: update
 *   Transition: request_changes
 *   Recipients: owner
 * Template 16: sol_propose_requested_changes
 *   Operation: update
 *   Transition: propose
 *   Source state: needs_update
 *   Recipients: moderator
 * Template 17: sol_deletion_no_approval
 *   Operation: delete
 *   Source state: validated, proposed
 *   Actor: moderator
 *   Recipients: owner
 * @codingStandardsIgnoreEnd
 */
class SolutionRdfSubscriber extends NotificationSubscriberBase implements EventSubscriberInterface {

  const TEMPLATE_APPROVE = 'sol_approve_proposed';
  const TEMPLATE_BLACKLIST = 'sol_blacklist';
  const TEMPLATE_DELETION_APPROVE = 'sol_deletion_approved';
  const TEMPLATE_DELETION_NO_APPROVAL = 'sol_deletion_no_approval';
  const TEMPLATE_DELETION_REJECT = 'sol_deletion_reject';
  const TEMPLATE_PROPOSE_CHANGES = 'sol_propose_changes';
  const TEMPLATE_PROPOSE_NEW = 'sol_propose_new';
  const TEMPLATE_PROPOSE_FROM_REQUEST_CHANGES = 'sol_propose_requested_changes';
  const TEMPLATE_PUBLISH_BLACKLISTED = 'sol_publish_backlisted';
  const TEMPLATE_REQUEST_CHANGES = 'sol_request_changes';
  const TEMPLATE_REQUEST_DELETION = 'sol_request_deletion';

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
   * Whether the entity has a published version.
   *
   * @var bool
   */
  protected $hasPublished;

  /**
   * The notification event.
   *
   * @var \Drupal\joinup_notification\Event\NotificationEvent
   */
  protected $event;

  /**
   * The source state of the solution.
   *
   * @var string
   */
  protected $fromState;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[NotificationEvents::RDF_ENTITY_CRUD] = [
      ['onCreate'],
      ['onUpdate'],
      ['onDelete'],
    ];

    return $events;
  }

  /**
   * {@inheritdoc}
   */
  protected function initialize(NotificationEvent $event) {
    parent::initialize($event);
    if ($this->entity->bundle() !== 'solution') {
      return;
    }

    $this->event = $event;
    $this->stateField = 'field_is_state';
    $this->workflow = $this->entity->get($this->stateField)->first()->getWorkflow();
    $this->fromState = isset($this->entity->original) ? $this->entity->original->get($this->stateField)->first()->value : '__new__';
    $to_state = $this->entity->get($this->stateField)->first()->value;
    $this->transition = $this->workflow->findTransition($this->fromState, $to_state);
    $this->motivation = empty($this->entity->motivation) ? '' : $this->entity->motivation;
    $this->hasPublished = $this->hasPublishedVersion($this->entity);
  }

  /**
   * Sends notification if the solution is created in proposed state.
   *
   * @param \Drupal\joinup_notification\Event\NotificationEvent $event
   *   The notification event.
   */
  public function onCreate(NotificationEvent $event) {
    $this->initialize($event);
    if (!$this->appliesOnCreate()) {
      return;
    }
    $template_id = self::TEMPLATE_PROPOSE_NEW;
    $user_data_array = ['roles' => ['moderator' => [$template_id]]];
    $user_data = $this->getUsersMessages($user_data_array);
    $this->sendUserDataMessages($user_data);
  }

  /**
   * Checks if the conditions apply for the onCreate method.
   *
   * @return bool
   *   Whether the conditions apply.
   */
  protected function appliesOnCreate() {
    if (!$this->appliesOnSolutions()) {
      return FALSE;
    }

    if ($this->operation !== 'create') {
      return FALSE;
    }

    if (empty($this->transition)) {
      return FALSE;
    }

    if ($this->transition->getId() !== 'propose') {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Handles notifications on solution update.
   *
   * @param \Drupal\joinup_notification\Event\NotificationEvent $event
   *   The notification event.
   */
  public function onUpdate(NotificationEvent $event) {
    $this->initialize($event);
    if (!$this->appliesOnUpdate()) {
      return;
    }

    switch ($this->transition->getId()) {
      case 'propose':
        $this->notificationPropose();
        break;

      case 'validate':
        $this->notificationValidate();
        break;

      // Notification ids handled: 10.
      case 'request_deletion':
        $user_data = ['roles' => ['moderator' => [self::TEMPLATE_REQUEST_DELETION]]];
        $this->getUsersAndSend($user_data);
        break;

      // Notification ids handled: 13.
      case 'blacklist':
        $user_data = [
          'og_roles' => [
            'rdf_entity-solution-administrator' => [
              self::TEMPLATE_BLACKLIST,
            ],
          ],
        ];
        $this->getUsersAndSend($user_data);
        break;

      // Notification ids handled: 15.
      case 'needs_update':
        $user_data = [
          'og_roles' => [
            'rdf_entity-solution-administrator' => [
              self::TEMPLATE_REQUEST_CHANGES,
            ],
          ],
        ];
        $this->getUsersAndSend($user_data);
        break;

    }
  }

  /**
   * Checks if the conditions apply for the onUpdate method.
   *
   * @return bool
   *   Whether the conditions apply.
   */
  protected function appliesOnUpdate() {
    if (!$this->appliesOnSolutions()) {
      return FALSE;
    }

    if ($this->operation !== 'update') {
      return FALSE;
    }

    if (empty($this->transition)) {
      return FALSE;
    }

    $transitions_with_notification = [
      'propose',
      'validate',
      'needs_update',
      'request_deletion',
      'blacklist',
    ];
    if (!in_array($this->transition->getId(), $transitions_with_notification)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Sends a notification for proposing a solution or proposing changes to it.
   *
   * Notifications ids handled: 1, 7, 16.
   */
  protected function notificationPropose() {
    switch ($this->fromState) {
      case 'validated':
        $user_data = [
          'og_roles' => [
            'rdf_entity-solution-administrator' => [
              self::TEMPLATE_PROPOSE_CHANGES,
            ],
          ],
        ];
        break;

      case 'needs_update':
        $user_data = ['roles' => ['moderator' => [self::TEMPLATE_PROPOSE_FROM_REQUEST_CHANGES]]];
        break;

      // The only case left is when the entity is proposed from the owner when
      // the entity is in draft. In this case, send the notification for new
      // entities.
      default:
        $user_data = ['roles' => ['moderator' => [self::TEMPLATE_PROPOSE_NEW]]];
        break;

    }

    $this->getUsersAndSend($user_data);
  }

  /**
   * Sends a notification for publishing a solution.
   *
   * Notification ids handled: 2, 12, 14.
   */
  protected function notificationValidate() {
    switch ($this->fromState) {
      case 'proposed':
        $user_data = [
          'og_roles' => [
            'rdf_entity-solution-administrator' => [
              self::TEMPLATE_APPROVE,
            ],
          ],
        ];
        break;

      case 'deletion_request':
        $user_data = [
          'og_roles' => [
            'rdf_entity-solution-administrator' => [
              self::TEMPLATE_DELETION_REJECT,
            ],
          ],
        ];
        break;

      case 'blacklisted':
        $user_data = [
          'og_roles' => [
            'rdf_entity-solution-administrator' => [
              self::TEMPLATE_PUBLISH_BLACKLISTED,
            ],
          ],
        ];
        break;

    }

    if (!empty($user_data)) {
      $this->getUsersAndSend($user_data);
    }
  }

  /**
   * Sends notification when a solution is deleted.
   *
   * Notifications handled: 11, 17.
   *
   * @param \Drupal\joinup_notification\Event\NotificationEvent $event
   *   The notification event.
   */
  public function onDelete(NotificationEvent $event) {
    $this->initialize($event);
    if (!$this->appliesOnDelete()) {
      return;
    }

    $template_id = $this->entity->get($this->stateField)->first()->value === 'deletion_request' ? self::TEMPLATE_DELETION_APPROVE : self::TEMPLATE_DELETION_NO_APPROVAL;
    $user_data = [
      'og_roles' => [
        'rdf_entity-solution-administrator' => [
          $template_id,
        ],
      ],
    ];
    $this->getUsersAndSend($user_data);
  }

  /**
   * Checks if the conditions apply for the onDelete method.
   *
   * @return bool
   *   Whether the conditions apply.
   */
  protected function appliesOnDelete() {
    if (!$this->appliesOnSolutions()) {
      return FALSE;
    }

    if ($this->operation !== 'delete') {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Checks if the event applies for solutions.
   *
   * @return bool
   *   Whether the event applies.
   */
  protected function appliesOnSolutions() {
    if ($this->entity->getEntityTypeId() !== 'rdf_entity') {
      return FALSE;
    }

    if ($this->entity->bundle() !== 'solution') {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigurationName() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  protected function generateArguments(EntityInterface $entity) {
    $arguments = parent::generateArguments($entity);
    $actor = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    $actor_first_name = $arguments['@actor:field_user_first_name'];
    $actor_last_name = $arguments['@actor:field_user_family_name'];
    $motivation = isset($this->entity->motivation) ? $this->entity->motivation : '';
    $arguments['@transition:motivation'] = $motivation;

    if (empty($arguments['@actor:role'])) {
      $membership = $this->membershipManager->getMembership($entity, $actor);
      if (!empty($membership)) {
        $role_names = array_map(function (OgRoleInterface $og_role) {
          return $og_role->getName();
        }, $membership->getRoles());

        if (in_array('administrator', $role_names)) {
          $arguments['@actor:role'] = t('Owner');
        }
        elseif (in_array('facilitator', $role_names)) {
          $arguments['@actor:role'] = t('Facilitator');
        }
      }
      $arguments['@actor:full_name'] = $actor_first_name . ' ' . $actor_last_name;
    }

    // For deletion requests, the titles of the affiliated collections are
    // provided.
    if (!empty($this->transition) && $this->transition->getId() === 'request_deletion') {
      $collection_ids = solution_get_collection_ids($this->entity);
      $collections = $this->entityTypeManager->getStorage('rdf_entity')->loadMultiple($collection_ids);
      $arguments['@solution:parents:title'] = implode(', ', array_map(function (RdfInterface $collection) {
        return $collection->label();
      }, $collections));
    }

    return $arguments;
  }

  /**
   * Checks whether the rdf entity has a published version.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return bool
   *   Whether the entity has a published version.
   *
   * @see: joinup_notification_rdf_entity_presave()
   */
  protected function hasPublishedVersion(EntityInterface $entity) {
    if (isset($entity->hasPublished)) {
      return ($entity->hasPublished);
    }

    return FALSE;
  }

  /**
   * Calculates the user data to send the messages with.
   *
   * @param array $user_data
   *   The user data array.
   *
   * @see: ::getUsersMessages() for more information on the array.
   */
  protected function getUsersAndSend(array $user_data) {
    $user_data = $this->getUsersMessages($user_data);
    $this->sendUserDataMessages($user_data);
  }

  /**
   * Returns the state of the solution related to the event.
   *
   * @return string
   *   The current state.
   */
  protected function getSolutionState() {
    return $this->entity->get('field_is_state')->first()->value;
  }

  /**
   * Checks whether the action is requested.
   *
   * Applies only for archival and deletion request.
   *
   * @return bool
   *   Whether the action is requested. Returns true if the current state is
   *    deletion_request and the operation is delete or if the current state is
   *    archival_request and the transition is archive. False otherwise.
   */
  protected function isTransitionRequested() {
    $state = $this->getSolutionState();
    if ($this->operation === 'delete') {
      return $state === 'deletion_request';
    }
    elseif ($state === 'archived') {
      return $this->transition->getId() === 'archive';
    }

    return FALSE;
  }

}
