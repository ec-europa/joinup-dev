<?php

declare(strict_types = 1);

namespace Drupal\joinup_notification\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\asset_release\Entity\AssetReleaseInterface;
use Drupal\joinup_group\JoinupGroupHelper;
use Drupal\joinup_notification\Event\NotificationEvent;
use Drupal\joinup_notification\MessageArgumentGenerator;
use Drupal\joinup_notification\NotificationEvents;
use Drupal\og\OgRoleInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Notification handler for the notifications related to releases.
 *
 * The notification base conditions are an extension of the solution templates.
 * @codingStandardsIgnoreStart
 * Template 18: release_update
 *   Operation: update
 *   Source state: published
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
 * @codingStandardsIgnoreEnd
 */
class ReleaseRdfSubscriber extends NotificationSubscriberBase implements EventSubscriberInterface {

  use StringTranslationTrait;

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
   * The new state of the solution.
   *
   * @var string
   */
  protected $toState;

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
  protected function initialize(NotificationEvent $event): void {
    parent::initialize($event);

    // Only initialize the workflow if it is available. It is unavailable when
    // the entity is being deleted during cleanup of orphaned group content.
    if (!$this->entity instanceof AssetReleaseInterface || !$this->entity->hasWorkflow()) {
      return;
    }

    $this->event = $event;
    $this->workflow = $this->entity->getWorkflow();
    $this->fromState = isset($this->entity->original) ? $this->entity->original->getWorkflowState() : '__new__';
    $this->toState = $this->entity->getWorkflowState();
    $this->transition = $this->workflow->findTransition($this->fromState, $this->toState);
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
  public function onUpdate(NotificationEvent $event): void {
    $this->initialize($event);
    if (!$this->appliesOnUpdate()) {
      return;
    }

    // Send notifications when a published release is updated.
    if ($this->fromState === $this->toState && $this->fromState === 'validated') {
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
    }

    $transition_id = $this->transition instanceof WorkflowTransition ? $this->transition->getId() : NULL;
    switch ($transition_id) {
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
  protected function appliesOnUpdate(): bool {
    if (!$this->appliesOnReleases()) {
      return FALSE;
    }

    if ($this->operation !== 'update') {
      return FALSE;
    }

    // Notifications can be sent for asset releases that are updated without
    // changing the workflow state.
    if ($this->fromState === $this->toState) {
      return TRUE;
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
  public function onDelete(NotificationEvent $event): void {
    $this->initialize($event);
    if (!$this->appliesOnDelete()) {
      return;
    }

    $user_data = [
      'roles' => [
        'moderator' => [
          self::TEMPLATE_DELETE,
        ],
      ],
      'og_roles' => [
        'rdf_entity-solution-administrator' => [
          self::TEMPLATE_DELETE,
        ],
        'rdf_entity-solution-facilitator' => [
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
  protected function appliesOnDelete(): bool {
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
  protected function appliesOnReleases(): bool {
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
  protected function getConfigurationName(): string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  protected function generateArguments(EntityInterface $entity): array {
    // PHP does not support covariance on arguments so we cannot narrow the type
    // hint to only asset release entities. Let's assert the type instead.
    assert($entity instanceof AssetReleaseInterface, __METHOD__ . ' only supports asset release entities.');

    $arguments = parent::generateArguments($entity);
    /** @var \Drupal\user\UserInterface $actor */
    $actor = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    $motivation = isset($this->entity->motivation) ? $this->entity->motivation : '';
    $arguments['@transition:motivation'] = $motivation;
    $arguments['@entity:field_isr_release_number'] = $entity->getVersion();

    // Add arguments related to the parent collection or solution.
    $parent = JoinupGroupHelper::getGroup($entity);
    if (!empty($parent)) {
      $arguments += MessageArgumentGenerator::getGroupArguments($parent);
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
        $arguments['@actor:full_name'] = $actor->getDisplayName();
      }
    }

    return $arguments;
  }

  /**
   * Calculates the user data to send the messages with.
   *
   * @param array $user_data
   *   The user data array.
   *
   * @see ::getUsersMessages()
   */
  protected function getUsersAndSend(array $user_data): void {
    $user_data = $this->getUsersMessages($user_data);
    $this->sendUserDataMessages($user_data);
  }

}
