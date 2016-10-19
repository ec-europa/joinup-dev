<?php

namespace Drupal\og_comment;

use Drupal\comment\CommentAccessControlHandler;
use Drupal\comment\CommentInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelperInterface;

/**
 * Defines the access control handler for the comment entity type.
 *
 * @see \Drupal\comment\Entity\Comment
 */
class OgCommentAccessControlHandler extends CommentAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $access = parent::checkAccess($entity, $operation, $account);
    if (!$entity instanceof CommentInterface) {
      throw new \Exception('Only comments can be handled.');
    }

    $host_entity = $entity->getCommentedEntity();
    // Is group content?
    if (!Og::isGroupContent($host_entity->getEntityTypeId(), $host_entity->bundle())) {
      return $access;
    }
    // Get group.
    $group_id = $host_entity->{OgGroupAudienceHelperInterface::DEFAULT_FIELD}->first()->target_id;
    if (!$group_id) {
      return $access;
    }
    /** @var FieldConfig $field_config */
    $field_config = $host_entity->{OgGroupAudienceHelperInterface::DEFAULT_FIELD}->first()->getFieldDefinition();
    /** @var FieldStorageConfig $storage_definition */
    $storage_definition = $field_config->getFieldStorageDefinition();
    $entity_type = $storage_definition->getSetting('target_type');

    $entity_storage = \Drupal::entityManager()->getStorage($entity_type);
    $group = $entity_storage->load($group_id);

    /** @var \Drupal\og\OgAccessInterface $og_access */
    $og_access = \Drupal::getContainer()->get('og.access');
    $access = $og_access->userAccess($group, 'moderate comments', $account);
    return $access;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return parent::checkCreateAccess($account, $context, $entity_bundle);
  }

}
