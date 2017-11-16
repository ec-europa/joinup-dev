<?php

declare(strict_types = 1);

namespace Drupal\joinup_invite\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Invitation entity.
 *
 * @ingroup joinup_invite
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
  public function getCreatedTime() : int {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime(int $timestamp) : InvitationInterface {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() : AccountInterface {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() : int {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) : InvitationInterface {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) : InvitationInterface {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) : array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('The user that has been invited.'))
      ->setRevisionable(FALSE)
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Group entity type'))
      ->setDescription(t('The entity type of the entity that the user was invited to.'));

    $fields['entity_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Group entity id'))
      ->setDescription(t('The ID of the entity that the user was invited to.'));

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setSetting('allowed_values', static::getStatuses())
      ->setDefaultValue(InvitationInterface::STATUS_PENDING)
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The UNIX timestamp indicating when the invitation was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The UNIX timestamp indicating when the invitation was last edited.'));

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
  public function label() : string {
    throw new \Exception(__METHOD__ . ' is not yet implemented');
  }

}
