<?php

namespace Drupal\joinup_notification\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\joinup_notification\Event\NotificationEvent;
use Drupal\joinup_notification\NotificationEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class CommentSubscriber.
 */
class CommentSubscriber extends NotificationSubscriberBase implements EventSubscriberInterface {

  const TEMPLATE_CREATE = 'comment_create';
  const TEMPLATE_UPDATE = 'comment_update';
  const TEMPLATE_DELETE = 'comment_delete';

  /**
   * The entity that the comment belongs to.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $parent;

  /**
   * The group that the entity of the comment belongs to.
   *
   * If the comment belongs to a group, then the group is the same as the parent
   * variable.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $group;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[NotificationEvents::COMMENT_CRUD] = [
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
    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = $this->entity;
    $this->parent = $comment->getCommentedEntity();
    if (!empty($this->parent)) {
      if ($this->groupTypeManager->isGroup($this->parent->getEntityTypeId(), $this->parent->bundle())) {
        $this->group = $this->parent;
      }
      elseif ($this->groupTypeManager->isGroupContent($this->parent->getEntityTypeId(), $this->parent->bundle())) {
        $this->group = $this->relationManager->getParent($this->parent);
      }
    }
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

    $user_data = ['roles' => ['moderator' => [self::TEMPLATE_CREATE]]];
    $user_data = $this->getUsersMessages($user_data);
    $this->sendUserDataMessages($user_data);
  }

  /**
   * Checks if the event applies for the create operation.
   *
   * @return bool
   *   Whether the event applies.
   */
  protected function appliesOnCreate() {
    if ($this->operation !== 'create') {
      return FALSE;
    }

    if (!$this->appliesOnComments()) {
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

    $user_data = ['roles' => ['moderator' => [self::TEMPLATE_UPDATE]]];
    $user_data = $this->getUsersMessages($user_data);
    $this->sendUserDataMessages($user_data);
  }

  /**
   * Checks if the event applies for the update operation.
   *
   * @return bool
   *   Whether the event applies.
   */
  protected function appliesOnUpdate() {
    if ($this->operation !== 'update') {
      return FALSE;
    }

    if (!$this->appliesOnComments()) {
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

    $user_data = ['owner' => [self::TEMPLATE_DELETE]];
    $user_data = $this->getUsersMessages($user_data);
    $this->sendUserDataMessages($user_data);
  }

  /**
   * Checks if the event applies for the delete operation.
   *
   * @return bool
   *   Whether the event applies.
   */
  protected function appliesOnDelete() {
    if ($this->operation !== 'delete') {
      return FALSE;
    }

    if (!$this->appliesOnComments()) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Checks if the event applies for the update operation.
   *
   * @return bool
   *   Whether the event applies.
   */
  protected function appliesOnComments() {
    if ($this->entity->getEntityTypeId() !== 'comment') {
      return FALSE;
    }

    // Skip notifications for orphaned comments.
    if (empty($this->group)) {
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
    // The parent is passed here instead so that the entity url will retrieve
    // the parent entity's url as the comments do not have one.
    $arguments = parent::generateArguments($this->parent);

    // Restore basic properties to match the comment entity instead of the
    // parent.
    /** @var \Drupal\comment\CommentInterface $entity */
    $arguments['@entity:title'] = $entity->label();
    $arguments['@entity:bundle'] = $entity->bundle();
    $arguments['@entity:url'] = $this->parent->toUrl('canonical', [
      'absolute' => TRUE,
      'fragment' => 'comment-' . $entity->id(),
    ])->toString();

    // Generate the rest of the properties.
    $arguments['@parent:title'] = $this->parent->label();
    $arguments['@parent:bundle'] = $this->parent->bundle();
    $arguments['@parent:url'] = $this->parent->toUrl('canonical', ['absolute' => TRUE])->toString();

    $arguments['@group:title'] = $this->group->label();
    $arguments['@group:bundle'] = $this->group->bundle();
    $arguments['@group:url'] = $this->group->toUrl('canonical', ['absolute' => TRUE])->toString();

    if ($this->currentUser->isAnonymous() || empty($arguments['@actor:full_name'])) {
      $arguments['@actor:full_name'] = $this->currentUser->isAnonymous() ? t('an anonymous user') : t('a Joinup user');
    }

    return $arguments;
  }

}
