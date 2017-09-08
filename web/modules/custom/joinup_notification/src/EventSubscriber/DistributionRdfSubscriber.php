<?php

namespace Drupal\joinup_notification\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\joinup_notification\Event\NotificationEvent;
use Drupal\joinup_notification\NotificationEvents;
use Drupal\og\OgRoleInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Notification handler for the notifications related to distributions.
 *
 * The notification base conditions are an extension of the solution templates.
 * @codingStandardsIgnoreStart
 * Template 21: release_update
 *   Operation: update
 *   Transition: update_published
 *   Recipients: solution owners, solution facilitators, moderators
 * Template 22: release_delete
 *   Operation: delete
 *   Source state: published, needs_update
 *   Recipients: owner
 * @codingStandardsIgnoreEnd
 */
class DistributionRdfSubscriber extends NotificationSubscriberBase implements EventSubscriberInterface {

  const TEMPLATE_UPDATE = 'distribution_update';
  const TEMPLATE_DELETE = 'distribution_delete';

  /**
   * The notification event.
   *
   * @var \Drupal\joinup_notification\Event\NotificationEvent
   */
  protected $event;

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
    if ($this->entity->bundle() !== 'asset_distribution') {
      return;
    }

    $this->event = $event;
  }

  /**
   * Handles notifications on distribution update.
   *
   * Notifications handled: 21.
   *
   * @param \Drupal\joinup_notification\Event\NotificationEvent $event
   *   The notification event.
   */
  public function onUpdate(NotificationEvent $event) {
    $this->initialize($event);
    if (!$this->appliesOnUpdate()) {
      return;
    }

    $user_data = [
      'og_roles' => [
        'rdf_entity-solution-administrator' => [
          self::TEMPLATE_UPDATE,
        ],
        'rdf_entity-solution-facilitator' => [
          self::TEMPLATE_UPDATE,
        ],
      ],
    ];
    $this->getUsersAndSend($user_data);
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

    return TRUE;
  }

  /**
   * Sends notification when a distribution is deleted.
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

    if ($this->entity->bundle() !== 'asset_distribution') {
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
    $arguments['@release:info:with_version'] = '';

    // Add arguments related to the parent collection or solution.
    $parent = asset_distribution_get_distribution_parent($entity);
    $solution = (!empty($parent) && $parent->bundle() === 'solution') ? $parent : $this->relationManager->getParent($entity);
    if (!empty($parent) && $parent->bundle() === 'asset_release') {
      $arguments['@release:info:with_version'] = t('of the release @release, @version', [
        '@release' => $parent->label(),
        '@version' => $parent->get('field_isr_release_number')->first()->value,
      ]);
    }
    if (!empty($solution)) {
      $arguments['@group:title'] = $solution->label();
      $arguments['@group:bundle'] = $solution->bundle();
      if (empty($arguments['@actor:role'])) {
        $membership = $this->membershipManager->getMembership($solution, $actor);
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
