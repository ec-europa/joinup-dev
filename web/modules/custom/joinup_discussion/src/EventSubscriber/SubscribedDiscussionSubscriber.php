<?php

declare(strict_types = 1);

namespace Drupal\joinup_discussion\EventSubscriber;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\joinup_discussion\Event\DiscussionDeleteEvent;
use Drupal\joinup_discussion\Event\DiscussionUpdateEvent;
use Drupal\joinup_discussion\Event\DiscussionEvents;
use Drupal\joinup_notification\JoinupMessageDeliveryInterface;
use Drupal\joinup_notification\MessageArgumentGenerator;
use Drupal\joinup_subscription\JoinupSubscriptionInterface;
use Drupal\node\NodeInterface;
use Drupal\rdf_entity\RdfInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to discussion CRUD operations.
 */
class SubscribedDiscussionSubscriber implements EventSubscriberInterface {

  /**
   * The Joinup subscribe service.
   *
   * @var \Drupal\joinup_subscription\JoinupSubscriptionInterface
   */
  protected $subscribeService;

  /**
   * The Joinup message delivery service.
   *
   * @var \Drupal\joinup_notification\JoinupMessageDeliveryInterface
   */
  protected $messageDelivery;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a new event subscriber object.
   *
   * @param \Drupal\joinup_subscription\JoinupSubscriptionInterface $subscribe_service
   *   The Joinup subscribe service.
   * @param \Drupal\joinup_notification\JoinupMessageDeliveryInterface $message_delivery
   *   The Joinup message delivery service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(JoinupSubscriptionInterface $subscribe_service, JoinupMessageDeliveryInterface $message_delivery, AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory) {
    $this->subscribeService = $subscribe_service;
    $this->messageDelivery = $message_delivery;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      DiscussionEvents::UPDATE => 'notifyOnDiscussionUpdate',
      DiscussionEvents::DELETE => 'notifyOnDiscussionDeletion',
    ];
  }

  /**
   * Reacts when a discussion is updated.
   *
   * @param \Drupal\joinup_discussion\Event\DiscussionUpdateEvent $event
   *   The event object.
   */
  public function notifyOnDiscussionUpdate(DiscussionUpdateEvent $event): void {
    $discussion = $event->getNode();

    // Don't handle inconsistent discussions, without a parent group.
    $group = $this->getDiscussionGroup($discussion);
    if (empty($group)) {
      return;
    }

    $this->sendMessage($discussion, 'discussion_updated');
  }

  /**
   * Sends notification to subscribed users when a discussion is deleted.
   *
   * @param \Drupal\joinup_discussion\Event\DiscussionDeleteEvent $event
   *   The event object.
   */
  public function notifyOnDiscussionDeletion(DiscussionDeleteEvent $event): void {
    $discussion = $event->getNode();
    $this->sendMessage($discussion, 'discussion_delete');
  }

  /**
   * Returns the list of subscribers.
   *
   * @param \Drupal\node\NodeInterface $discussion
   *   The discussion for which to return the subscribers.
   *
   * @return \Drupal\user\UserInterface[]
   *   The list of subscribers as an array of user accounts, keyed by user ID.
   */
  protected function getSubscribers(NodeInterface $discussion): array {
    $subscribers = [];

    // The discussion owner is added to the list of subscribers.
    $owner = $discussion->getOwner();
    if (!empty($owner) && !$owner->isAnonymous()) {
      $subscribers[$owner->id()] = $owner;
    }
    $subscribers += $this->subscribeService->getSubscribers($discussion, 'subscribe_discussions');

    // Exclude the current user from the list.
    $current_user = $this->getCurrentUser();
    return array_filter($subscribers, function (AccountInterface $subscriber) use ($current_user) {
      return $subscriber->id() != $current_user->id();
    });
  }

  /**
   * Returns the message arguments.
   *
   * @param \Drupal\node\NodeInterface $discussion
   *   The discussion for which to return the arguments.
   *
   * @return array
   *   An associative array of message arguments, keyed by argument ID.
   *
   * @throws \Exception
   *   Thrown if one of the arguments could not be generated.
   */
  protected function getArguments(NodeInterface $discussion): array {
    $arguments = [];

    $arguments['@entity:title'] = $discussion->label();
    try {
      $entity_url = $discussion->toUrl('canonical', ['absolute' => TRUE])->toString();
    }
    catch (EntityMalformedException | UndefinedLinkTemplateException $e) {
      // Retrieval of the URL might fail if the link template is not defined, or
      // if the entity doesn't have an ID.
      throw new \Exception('Could not retrieve URL for discussion.', NULL, $e);
    }
    $arguments['@entity:url'] = $entity_url;

    $actor = $this->getCurrentUser();
    $actor_first_name = !empty($actor->get('field_user_first_name')->value) ? $actor->get('field_user_first_name')->value : '';
    $actor_family_name = !empty($actor->get('field_user_family_name')->value) ? $actor->get('field_user_family_name')->value : '';

    if ($actor->hasRole('moderator')) {
      $arguments['@actor:full_name'] = 'The Joinup Support Team';
    }
    else {
      $arguments['@actor:full_name'] = empty($actor->get('full_name')->value) ? $actor_first_name . ' ' . $actor_family_name : $actor->get('full_name')->value;
    }

    $group = $this->getDiscussionGroup($discussion);
    if ($group) {
      $arguments += MessageArgumentGenerator::getGroupArguments($group);
    }

    return $arguments;
  }

  /**
   * Returns the fully loaded User entity for the current user.
   *
   * @return \Drupal\user\UserInterface
   *   The current user.
   */
  protected function getCurrentUser(): UserInterface {
    try {
      /** @var \Drupal\user\UserInterface $user */
      $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
      return $user;
    }
    catch (InvalidPluginDefinitionException $e) {
      // The storage for the 'user' entity type should exist. If it didn't
      // Drupal would be completely broken. This code should never run but we
      // are returning an anonymous user entity for completeness and to satisfy
      // the inspections of the IDE.
      return new User([], 'user');
    }
  }

  /**
   * Sends the notification to the recipients.
   *
   * @param \Drupal\node\NodeInterface $discussion
   *   The discussion for which to send the notification.
   * @param string $message_template
   *   The ID of the message template to use.
   *
   * @return bool
   *   Whether or not the sending of the e-mails has succeeded.
   */
  protected function sendMessage(NodeInterface $discussion, string $message_template): bool {
    try {
      $success = TRUE;
      // Create individual messages for each subscriber so that we can honor the
      // user's chosen digest frequency.
      foreach ($this->getSubscribers($discussion) as $subscriber) {
        $notifier_options = [
          'entity_type' => $discussion->getEntityTypeId(),
          'entity_id' => $discussion->id(),
        ];
        $success = $this->messageDelivery->sendMessageTemplateToUser($message_template, $this->getArguments($discussion), $subscriber, $notifier_options, TRUE) && $success;
      }
      return $success;
    }
    catch (\Exception $e) {
      $context = ['exception' => $e];
      $this->loggerFactory->get('mail')->critical('Unexpected exception thrown when sending a message for a discussion.', $context);
      return FALSE;
    }
  }

  /**
   * Returns the group the discussion belongs to.
   *
   * @param \Drupal\node\NodeInterface $discussion
   *   The discussion for which to return the group.
   *
   * @return \Drupal\rdf_entity\RdfInterface|null
   *   The group (which is an RDF entity of type 'collection' or 'solution'),
   *   or NULL if no group is found.
   */
  protected function getDiscussionGroup(NodeInterface $discussion): ?RdfInterface {
    return $discussion->get('og_audience')->entity;
  }

}
