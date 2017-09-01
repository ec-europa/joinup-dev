<?php

namespace Drupal\joinup_notification\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\joinup_notification\Event\NotificationEvent;
use Drupal\joinup_notification\NotificationEvents;
use Drupal\og\OgRoleInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Notification handler for the notifications related to releases.
 *
 * The notification base conditions are an extension of the solution templates.
 * @codingStandardsIgnoreStart
 * Template 18: release_update
 *   Operation: update
 *   Transition: update_published
 *   Recipients: solution owners, solution facilitators, moderators
 * Template 19: release_delete
 *   Operation: delete
 *   Source state: published, needs_update
 *   Recipients: owner
 * Template 20: release_approve_proposed
 *   Operation: update
 *   Transition: validate
 *   Source state: needs_update
 *   Recipients: owner
 * Template 23: release_request_changes
 *   Operation: update
 *   Transition: request_deletion
 *   Recipients: owner
 * @codingStandardsIgnoreStart
 */
class ReleaseRdfSubscriber extends NotificationSubscriberBase implements EventSubscriberInterface {

  const TEMPLATE_UPDATE_PUBLISHED = 'release_update';
  const TEMPLATE_DELETE = 'release_delete';
  const TEMPLATE_APPROVE_PROPOSED = 'release_approve_proposed';
  const TEMPLATE_REQUEST_CHANGES = 'release_request_changes';

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
    if ($this->entity->bundle() !== 'asset_release') {
      return;
    }

    $this->event = $event;
    $this->stateField = 'field_isr_state';
    $this->workflow = $this->entity->get($this->stateField)->first()->getWorkflow();
    $this->fromState = isset($this->entity->original) ? $this->entity->original->get($this->stateField)->first()->value : '__new__';
    $to_state = $this->entity->get($this->stateField)->first()->value;
    $this->transition = $this->workflow->findTransition($this->fromState, $to_state);
    $this->motivation = empty($this->entity->motivation) ? '' : $this->entity->motivation;
  }

  /**
   * Handles notifications on release update.
   *
   * Notifications handled: 18, 20, 23.
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
      case 'update_published':
        $user_data = [
          'roles' => [
            'moderator' => [
              self::TEMPLATE_UPDATE_PUBLISHED,
            ],
          ],
          'og_roles' => [
            'rdf_entity-solution-administrator' => [
              self::TEMPLATE_UPDATE_PUBLISHED,
            ],
            'rdf_entity-solution-facilitator' => [
              self::TEMPLATE_UPDATE_PUBLISHED,
            ],
          ],
        ];
        $this->getUsersAndSend($user_data);
        break;

      case 'validate':
        if ($this->fromState === 'needs_update') {
          $user_data = [
            'og_roles' => [
              'rdf_entity-solution-administrator' => [
                self::TEMPLATE_APPROVE_PROPOSED,
              ],
            ],
          ];
          $this->getUsersAndSend($user_data);
        }
        break;

      // Notification ids handled: 10.
      case 'request_changes':
        $user_data = [
          'og_roles' => [
            'rdf_entity-solution-administrator' => [
              self::TEMPLATE_REQUEST_CHANGES,
            ],
            'rdf_entity-solution-facilitator' => [
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
    if (!$this->appliesOnReleases()) {
      return FALSE;
    }

    if ($this->operation !== 'update') {
      return FALSE;
    }

    if (empty($this->transition)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Sends notification when a release is deleted.
   *
   * Notifications handled: 19.
   *
   * @param \Drupal\joinup_notification\Event\NotificationEvent $event
   *   The notification event.
   */
  public function onDelete(NotificationEvent $event) {
    $this->initialize($event);
    if (!$this->appliesOnDelete()) {
      return;
    }

    $user_data = [
      'og_roles' => [
        'rdf_entity-solution-administrator' => [
          self::TEMPLATE_DELETE,
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
    if (!$this->appliesOnReleases()) {
      return FALSE;
    }

    if ($this->operation !== 'delete') {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Checks if the event applies for releases.
   *
   * @return bool
   *   Whether the event applies.
   */
  protected function appliesOnReleases() {
    if ($this->entity->getEntityTypeId() !== 'rdf_entity') {
      return FALSE;
    }

    if ($this->entity->bundle() !== 'asset_release') {
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
    $arguments['@entity:field_isr_release_number'] = !empty($entity->get('field_isr_release_number')->first()->value) ? $entity->get('field_isr_release_number')->first()->value : '';

    // Add arguments related to the parent collection or solution.
    $parent = $this->relationManager->getParent($entity);
    if (!empty($parent)) {
      $arguments['@group:title'] = $parent->label();
      $arguments['@group:bundle'] = $parent->bundle();
      if (empty($arguments['@actor:role'])) {
        $membership = $this->membershipManager->getMembership($parent, $actor);
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
    }

    return $arguments;
  }

  /**
   * Calculates the user data to send the messages with.
   *
   * @param array $user_data
   *    The user data array.
   *
   * @see: ::getUsersMessages() for more information on the array.
   */
  protected function getUsersAndSend(array $user_data) {
    $user_data = $this->getUsersMessages($user_data);
    $this->sendUserDataMessages($user_data);
  }

  /**
   * Returns the state of the release related to the event.
   *
   * @return string
   *    The current state.
   */
  protected function getReleaseState() {
    return $this->entity->get('field_is_state')->first()->value;
  }

}
