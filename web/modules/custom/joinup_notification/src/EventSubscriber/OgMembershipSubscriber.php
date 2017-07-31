<?php

namespace Drupal\joinup_notification\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\joinup_notification\Event\NotificationEvent;
use Drupal\joinup_notification\NotificationEvents;
use Drupal\og\OgMembershipInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class OgMembershipSubscriber.
 */
class OgMembershipSubscriber extends NotificationSubscriberBase implements EventSubscriberInterface {

  const TEMPLATE_REQUEST_MEMBERSHIP = 'og_membership_request';
  const TEMPLATE_APPROVE_REJECT_MEMBERSHIP = 'og_membership_decision';

  /**
   * The membership object.
   *
   * @var \Drupal\og\Entity\OgMembership
   */
  protected $membership;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[NotificationEvents::OG_MEMBERSHIP_MANAGEMENT] = [
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
    /** @var \Drupal\og\Entity\OgMembership $membership */
    $this->membership = $event->getEntity();
    $this->entity = $this->membership->getGroup();
    $this->operation = $event->getOperation();
    $this->config = $this->getConfigurationName();
  }

  /**
   * Sends notification when a membership is requested.
   *
   * @param \Drupal\joinup_notification\Event\NotificationEvent $event
   *   The notification event.
   */
  public function onCreate(NotificationEvent $event) {
    $this->initialize($event);
    if (!$this->appliesOnCreate()) {
      return;
    }

    $template_id = self::TEMPLATE_REQUEST_MEMBERSHIP;
    $user_data_array = [
      'og_roles' => [
        'rdf_entity-collection-administrator' => [
          $template_id,
        ],
        'rdf_entity-solution-administrator' => [
          $template_id,
        ],
      ],
    ];

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
    if ($this->operation !== 'create') {
      return FALSE;
    }

    if ($this->membership->getState() !== OgMembershipInterface::STATE_PENDING) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Sends notifications when a membership is approved.
   *
   * Notifications handled: All.
   *
   * @param \Drupal\joinup_notification\Event\NotificationEvent $event
   *   The notification event.
   */
  public function onUpdate(NotificationEvent $event) {
    $this->initialize($event);
    if (!$this->appliesOnUpdate()) {
      return;
    }

    $recipient_id = $this->membership->getUser()->id();
    $user_data = [
      self::TEMPLATE_APPROVE_REJECT_MEMBERSHIP => [
        $recipient_id => $recipient_id,
      ],
    ];
    $this->sendUserDataMessages($user_data);
  }

  /**
   * Checks if the conditions apply for the onUpdate method.
   *
   * @return bool
   *   Whether the conditions apply.
   */
  protected function appliesOnUpdate() {
    if ($this->operation !== 'update') {
      return FALSE;
    }

    if (empty($this->membership->original)) {
      return FALSE;
    }

    /** @var \Drupal\og\Entity\OgMembership $original_membership */
    $original_membership = $this->membership->original;
    // Only send an email when a membership is approved.
    if ($original_membership->getState() !== OgMembershipInterface::STATE_PENDING || $this->membership->getState() !== OgMembershipInterface::STATE_ACTIVE) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Sends notifications when a membership is rejected(deleted).
   *
   * @param \Drupal\joinup_notification\Event\NotificationEvent $event
   *   The notification event.
   */
  public function onDelete(NotificationEvent $event) {
    $this->initialize($event);
    if (!$this->appliesOnDelete()) {
      return;
    }

    $recipient_id = $this->membership->getUser()->id();
    $user_data = [
      self::TEMPLATE_APPROVE_REJECT_MEMBERSHIP => [
        $recipient_id => $recipient_id,
      ],
    ];
    $this->sendUserDataMessages($user_data);
  }

  /**
   * Checks if the conditions apply for the onDelete method.
   *
   * @return bool
   *   Whether the conditions apply.
   */
  protected function appliesOnDelete() {
    if ($this->operation !== 'delete') {
      return FALSE;
    }

    // Avoid sending notifications if the deletion of the membership is a result
    // of deleting the group.
    if (empty($this->membership->getGroup())) {
      return FALSE;
    }

    // Do not send a membership rejection e-email if the state of the membership
    // is not pending as it is not a reject but a delete.
    if ($this->membership->getState() !== OgMembershipInterface::STATE_PENDING) {
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
  protected function generateArguments(EntityInterface $message) {
    $arguments = parent::generateArguments($message);
    $actor_first_name = $arguments['@actor:field_user_first_name'];
    $actor_last_name = $arguments['@actor:field_user_family_name'];
    $arguments['@actor:full_name'] = $actor_first_name . ' ' . $actor_last_name;

    // Calculate extra arguments per case.
    switch ($this->operation) {
      case 'create':
        $arguments['@group:members_page:url'] = $this->getMembersUrl();
        break;

      case 'update':
        $arguments['@membership:decision:state'] = 'approved';
        break;

      case 'delete':
        $arguments['@membership:decision:state'] = 'rejected';
        break;

    }

    return $arguments;
  }

  /**
   * Returns the member's page of the group.
   *
   * @return string
   *   The url.
   */
  protected function getMembersUrl() {
    $entity_type_id = $this->entity->getEntityTypeId();
    $route_name = "entity.{$entity_type_id}.og_admin_routes.members";
    $route_parameters = [
      $entity_type_id => $this->entity->id(),
    ];
    $url = Url::fromRoute($route_name, $route_parameters, ['absolute' => TRUE])->toString();
    return $url;
  }

}
