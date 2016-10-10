<?php

namespace Drupal\joinup_notification\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Config\ConfigManager;
use Drupal\message_notify\MessageNotifier;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\og\OgRoleManager;

/**
 * Class WorkflowTransitionEventSubscriber.
 *
 * @package Drupal\joinup_notification
 */
class WorkflowTransitionEventSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\message_notify\MessageNotifier definition.
   *
   * @var Drupal\message_notify\MessageNotifier
   */
  protected $message_notify_sender;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var Drupal\Core\Entity\EntityTypeManager
   */
  protected $entity_type_manager;

  /**
   * Drupal\og\OgRoleManager definition.
   *
   * @var Drupal\og\OgRoleManager
   */
  protected $og_role_manager;

  /**
   * Constructor.
   */
  public function __construct(MessageNotifier $message_notify_sender, EntityTypeManager $entity_type_manager, OgRoleManager $og_role_manager) {
    $this->message_notify_sender = $message_notify_sender;
    $this->entity_type_manager = $entity_type_manager;
    $this->og_role_manager = $og_role_manager;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {

    return $events;
  }


}
