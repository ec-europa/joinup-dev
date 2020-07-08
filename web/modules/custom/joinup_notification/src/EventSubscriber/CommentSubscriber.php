<?php

declare(strict_types = 1);

namespace Drupal\joinup_notification\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\joinup_group\JoinupGroupHelper;
use Drupal\joinup_notification\Event\NotificationEvent;
use Drupal\joinup_notification\MessageArgumentGenerator;
use Drupal\joinup_notification\NotificationEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles notifications related to comments.
 */
class CommentSubscriber extends NotificationSubscriberBase implements EventSubscriberInterface {

  use StringTranslationTrait;

  const TEMPLATE_CREATE = 'comment_create';
  const TEMPLATE_UPDATE = 'comment_update';
  const TEMPLATE_DELETE = 'comment_delete';
  const RECIPIENTS = [
    'create' => [
      'roles' => ['moderator' => [self::TEMPLATE_CREATE]],
      'og_roles' => [
        'rdf_entity-collection-administrator' => [self::TEMPLATE_CREATE],
        'rdf_entity-solution-administrator' => [self::TEMPLATE_CREATE],
        'rdf_entity-collection-facilitator' => [self::TEMPLATE_CREATE],
        'rdf_entity-solution-facilitator' => [self::TEMPLATE_CREATE],
      ],
    ],
    'update' => ['roles' => ['moderator' => [self::TEMPLATE_UPDATE]]],
    'delete' => ['owner' => [self::TEMPLATE_DELETE]],
  ];

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
        $this->group = JoinupGroupHelper::getGroup($this->parent);
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

    $user_data = self::RECIPIENTS['create'];
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

    $user_data = self::RECIPIENTS['update'];
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

    $user_data = self::RECIPIENTS['delete'];
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
  protected function generateArguments(EntityInterface $entity): array {
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

    $arguments += MessageArgumentGenerator::getGroupArguments($this->group);

    if ($this->currentUser->isAnonymous() || empty($arguments['@actor:full_name'])) {
      $arguments['@actor:full_name'] = $this->currentUser->isAnonymous() ? $this->t('an anonymous user') : $this->t('a Joinup user');
    }

    return $arguments;
  }

}
