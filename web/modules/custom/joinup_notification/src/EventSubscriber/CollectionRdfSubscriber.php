<?php

namespace Drupal\joinup_notification\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\joinup_notification\Event\NotificationEvent;
use Drupal\joinup_notification\NotificationEvents;
use Drupal\og\OgRoleInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class CommunityContentSubscriber.
 */
class CollectionRdfSubscriber extends NotificationSubscriberBase implements EventSubscriberInterface {

  /**
   * The operation string.
   *
   * @var string
   */
  protected $operation;

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
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[NotificationEvents::RDF_ENTITY_CRUD] = [
      # Notification id 1 - Propose new collection.
      ['onCreate'],
      # All notification Ids.
      ['onUpdate'],
      # Notification id 9, 12, 13, 14.
      ['onDelete'],
    ];

    return $events;
  }

  /**
   * {@inheritdoc}
   */
  protected function initialize(NotificationEvent $event) {
    parent::initialize($event);
    $this->stateField = 'field_ar_state';
    $this->workflow = $this->entity->get($this->stateField)->first()->getWorkflow();
    $from_state = isset($this->entity->original) ? $this->entity->original->get($this->stateField)->first()->value : '__new__';
    $to_state = $this->entity->get($this->stateField)->first()->value;
    $this->transition = $this->workflow->findTransition($from_state, $to_state);
    $this->motivation = empty($this->entity->motivation) ? '' : $this->entity->motivation;
    $this->hasPublished = $this->hasPublishedVersion($this->entity);
  }

  /**
   * Sends notification if the collection is created in proposed state.
   *
   * Notifications handled: 1.
   *
   * @param \Drupal\joinup_notification\Event\NotificationEvent $event
   *    The notification event.
   */
  public function onCreate(NotificationEvent $event) {
    $this->initialize($event);
    if (!$this->appliesOnCreate()) {
      return;
    }

    $this->notificationPropose();
  }

  /**
   * Checks if the conditions apply for the onCreate method.
   *
   * @return bool
   *    Whether the conditions apply.
   */
  protected function appliesOnCreate() {
    if (!$this->appliesOnCollections()) {
      return FALSE;
    }

    if ($this->operation !== 'create') {
      return FALSE;
    }

    if ($this->transition->getId() !== 'propose') {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Handles notifications on collection update.
   *
   * Notifications handled: All.
   *
   * @param \Drupal\joinup_notification\Event\NotificationEvent $event
   *    The notification event.
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

      case 'request_archival':
      case 'request_deletion':
        $this->notificationRequestArchivalDeletion();
        break;

      case 'reject_archival':
      case 'reject_deletion':
        $this->notificationRejectArchivalDeletion();
        break;

      case 'archive':
        $this->notificationArchive();
        break;

    }
  }

  /**
   * Checks if the conditions apply for the onUpdate method.
   *
   * @return bool
   *    Whether the conditions apply.
   */
  protected function appliesOnUpdate() {
    if (!$this->appliesOnCollections()) {
      return FALSE;
    }

    if ($this->operation !== 'update') {
      return FALSE;
    }

    $transitions_with_notification = [
      'propose',
      'validate',
      'request_archival',
      'request_deletion',
      'reject_archival',
      'reject_deletion',
      'archive',
    ];
    if (!in_array($this->transition->getId(), $transitions_with_notification)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Sends notification if the collection is deleted.
   *
   * Notifications handled: 9, 12, 13, 14.
   *
   * @param \Drupal\joinup_notification\Event\NotificationEvent $event
   *    The notification event.
   */
  public function onDelete(NotificationEvent $event) {
    $this->initialize($event);
    if (!$this->appliesOnCreate()) {
      return;
    }

    $this->notificationPropose();
  }

  /**
   * Checks if the conditions apply for the onDelete method.
   *
   * @return bool
   *    Whether the conditions apply.
   */
  protected function appliesOnDelete() {
    if (!$this->appliesOnCollections()) {
      return FALSE;
    }

    if ($this->operation !== 'delete') {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Sends a notification for proposing a collection.
   *
   * Notification id 1, 4, 5.
   * Notification 1 is sent when the entity has not been published yet. 4 and 5
   * are sent when the proposal is by editing a published version.
   */
  protected function notificationPropose() {
    // @todo: TBD
  }

  /**
   * Sends a notification for validating a collection.
   *
   * Notification id 2, 6.
   * Notification 1 is sent when a new entity is published. 6 is sent when a
   * modification is approved.
   */
  protected function notificationValidate() {
    // @todo: TBD
  }

  /**
   * Sends a notification on archival/deletion request.
   *
   * Notification id 8.
   */
  protected function notificationRequestArchivalDeletion() {
    // @todo: TBD
  }

  /**
   * Sends a notification on rejecting an archival/deletion request.
   *
   * Notification id 10.
   */
  protected function notificationRejectArchivalDeletion() {
    // @todo: TBD
  }

  /**
   * Sends a notification on archiving a collection.
   *
   * Notification id 9, 12, 13, 14.
   */
  protected function notificationArchive() {
    // @todo: TBD
  }

  /**
   * Checks if the event applies for the update operation.
   *
   * @return bool
   *   Whether the event applies.
   */
  protected function appliesOnCollections() {
    if ($this->entity->getEntityTypeId() !== 'rdf_entity') {
      return FALSE;
    }

    if ($this->entity->bundle() !== 'collection') {
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
    $parent = $this->relationManager->getParent($entity);
    $actor = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    $actor_first_name = $arguments['@actor:field_user_first_name'];
    $actor_last_name = $arguments['@actor:field_user_family_name'];
    $motivation = isset($this->entity->motivation) ? $this->entity->motivation : '';

    $arguments['@transition:motivation'] = $motivation;
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

    if ($this->transition->getId() === 'archive') {
      $arguments['@transition:request_action'] = 'archive';
      $arguments['@transition:request_action:past'] = 'archived';
      $arguments['@transition:motivation'] = t('You can verify the outcome of your request by clicking on @entity:url.', [
        '@entity:url' => $arguments['@entity:url'],
      ]);
    }
    elseif ($this->operation === 'delete') {
      $arguments['@transition:request_action'] = 'delete';
      $arguments['@transition:request_action:past'] = 'deleted';
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
   */
  protected function hasPublishedVersion(EntityInterface $entity) {
    /** @var \Drupal\rdf_entity\RdfInterface $entity */
    if ($entity->isNew()) {
      return FALSE;
    }

    return $entity->hasGraph('default');
  }

}
