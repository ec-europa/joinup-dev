<?php

declare(strict_types = 1);

namespace Drupal\joinup_invite\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Invitation entity.
 *
 * This entity can be used to invite a user to interact with a certain entity.
 * Examples are to participate in a discussion, attend an event or join a
 * collection.
 *
 * The Invitation entity requires to have a User and an Entity associated with
 * it, and these cannot be changed after the Invitation is saved.
 *
 * If you want to send a Message along with the invitation, see the
 * InvitationMessageHelperInterface for instructions on how to create the
 * message template.
 *
 * @see \Drupal\joinup_invite\InvitationMessageHelperInterface
 *
 * An example implementation of using Invitations to invite users to participate
 * in a discussion can be found in the InviteToDiscussionForm.
 *
 * @see \Drupal\joinup_discussion\Form\InviteToDiscussionForm
 *
 * @ContentEntityType(
 *   id = "invitation",
 *   label = @Translation("Invitation"),
 *   bundle_label = @Translation("Invitation type"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "invitation",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "bundle",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   bundle_entity_type = "invitation_type"
 * )
 */
class Invitation extends ContentEntityBase implements InvitationInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime(): int {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime(int $timestamp): InvitationInterface {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner(): AccountInterface {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId(): int {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid): InvitationInterface {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account): InvitationInterface {
    return $this->setOwnerId($account->id());
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity(): ContentEntityInterface {
    $entity_type = $this->get('entity_type')->value;
    $entity_id = $this->get('entity_id')->value;

    if (empty($entity_type) || empty($entity_id)) {
      return NULL;
    }

    return \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id);
  }

  /**
   * {@inheritdoc}
   */
  public function setEntity(ContentEntityInterface $entity): InvitationInterface {
    // Only allow to change the entity on new invitations. An invitation is
    // bound to a user and an entity and these should not be changed once the
    // invitation is saved. Instead a new invitation should be created.
    if (!$this->isNew()) {
      throw new \RuntimeException('The entity cannot be changed for an existing invitation.');
    }
    $this->set('entity_type', $entity->getEntityTypeId());
    $this->set('entity_id', $entity->id());

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipient(): AccountInterface {
    return $this->get('recipient_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipientId(): int {
    return $this->get('recipient_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setRecipient(AccountInterface $recipient): InvitationInterface {
    return $this->setRecipientId((int) $recipient->id());
  }

  /**
   * {@inheritdoc}
   */
  public function setRecipientId(int $recipient_id): InvitationInterface {
    $this->set('recipient_id', $recipient_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): string {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus(string $status): InvitationInterface {
    $acceptable_statuses = [
      InvitationInterface::STATUS_PENDING,
      InvitationInterface::STATUS_ACCEPTED,
      InvitationInterface::STATUS_REJECTED,
    ];
    if (!in_array($status, $acceptable_statuses)) {
      throw new \InvalidArgumentException("Invalid status $status. Use one of: " . implode(', ', $acceptable_statuses));
    }

    $this->set('status', $status);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function accept(): InvitationInterface {
    $this->setStatus(InvitationInterface::STATUS_ACCEPTED);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function reject(): InvitationInterface {
    $this->setStatus(InvitationInterface::STATUS_REJECTED);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['recipient_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Recipient'))
      ->setDescription(t('The user that has been invited.'))
      ->setRevisionable(FALSE)
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE);

    // We store the entity type and entity ID as separate base fields since
    // entity reference fields expect a fixed entity type to be defined in the
    // field storage. We want to support entities of different types.
    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Group entity type'))
      ->setDescription(t('The entity type of the entity that the user was invited to.'))
      ->setRequired(TRUE);

    $fields['entity_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Group entity id'))
      ->setDescription(t('The ID of the entity that the user was invited to.'))
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setSetting('allowed_values', static::getStatuses())
      ->setDefaultValue(InvitationInterface::STATUS_PENDING)
      ->setRequired(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setDescription(t('The user that made the invitation.'))
      ->setRevisionable(FALSE)
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(static::class . '::getCurrentUserId')
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The UNIX timestamp indicating when the invitation was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The UNIX timestamp indicating when the invitation was last updated.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function getStatuses(): array {
    return [
      InvitationInterface::STATUS_PENDING => t('Pending'),
      InvitationInterface::STATUS_ACCEPTED => t('Accepted'),
      InvitationInterface::STATUS_REJECTED => t('Rejected'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    throw new \Exception(__METHOD__ . ' is not yet implemented');
  }

  /**
   * {@inheritdoc}
   */
  public function set($name, $value, $notify = TRUE): InvitationInterface {
    // Only allow to change the recipient or the entity on new invitations. An
    // invitation is bound to these parameters and they should not be changed
    // once the invitation is saved. Instead a new invitation should be created.
    if (in_array($name, ['recipient_id', 'entity_type', 'entity_id']) && !$this->isNew()) {
      throw new \RuntimeException("The '$name' cannot be changed for an existing invitation.");
    }
    return parent::set($name, $value, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    // Do not allow to store an invitation if the user or entity is missing.
    $recipient = $this->getRecipient();
    if ($recipient->isAnonymous()) {
      throw new \LogicException('Only registered user can receive invitations.');
    }

    if (!$entity = $this->getEntity()) {
      throw new \LogicException('An entity is required for creating an invitation.');
    }

    // Do not allow multiple invitations to be saved for a particular user and
    // entity.
    $existing_invitation = static::loadByEntityAndUser($entity, $recipient);
    if (!empty($existing_invitation) && ($entity->isNew() || $existing_invitation->id() !== $entity->id())) {
      throw new \Exception("An invitation already exists for {$entity->getEntityType()->getLabel()} '{$entity->label()}' and user '{$recipient->getAccountName()}'.");
    }

    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByEntityAndUser(ContentEntityInterface $entity, AccountInterface $recipient, string $bundle = ''): ?InvitationInterface {
    $storage = \Drupal::entityTypeManager()->getStorage('invitation');
    $invitations = $storage->loadByProperties([
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
      'recipient_id' => $recipient->id(),
      'bundle' => $bundle,
    ]);
    return !empty($invitations) ? reset($invitations) : NULL;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

}
