<?php

declare(strict_types = 1);

namespace Drupal\joinup_notification\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\joinup_notification\Event\NotificationEvent;
use Drupal\joinup_notification\NotificationEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Notification handler for the notifications related to shared entities.
 */
class ShareNotificationSubscriber extends NotificationSubscriberBase implements EventSubscriberInterface {

  const TEMPLATE_SOLUTION_SHARE = 'solution_share';

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
    $events[NotificationEvents::SOLUTION_SHARING] = [['onShare']];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  protected function initialize(NotificationEvent $event): void {
    parent::initialize($event);
    if ($this->entity->bundle() !== 'solution') {
      return;
    }

    $this->event = $event;
  }

  /**
   * Sends notification when the solution is shared in a collection.
   *
   * @param \Drupal\joinup_notification\Event\NotificationEvent $event
   *   The notification event.
   */
  public function onShare(NotificationEvent $event): void {
    $template_id = self::TEMPLATE_SOLUTION_SHARE;
    $user_data_array = [
      'og_roles' => [
        'rdf_entity-collection-administrator' => [$template_id],
        'rdf_entity-collection-facilitator' => [$template_id],
      ],
      'roles' => [
        'moderator' => [$template_id],
      ],
    ];

    $user_data = $this->getUsersMessages($user_data_array);
    $this->sendUserDataMessages($user_data);
  }

  /**
   * {@inheritdoc}
   */
  protected function generateArguments(EntityInterface $entity): array {
    $arguments = parent::generateArguments($entity);
    $arguments['collection_shared_labels'] = implode(', ', $entity->get('collection_shared_labels'));
    return $arguments;
  }

}
