<?php

namespace Drupal\og_comment;

use Drupal\comment\CommentAccessControlHandler;
use Drupal\comment\CommentInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
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
    /** @var \Drupal\comment\CommentInterface|\Drupal\user\EntityOwnerInterface $entity */
    $access = parent::checkAccess($entity, $operation, $account);
    if ($access instanceof AccessResultAllowed) {
      return $access;
    }

    $comment_admin = $this->hasPermission($entity, $account, 'administer comments');
    if ($operation == 'approve') {
      return AccessResult::allowedIf($comment_admin && !$entity->isPublished())
        ->cachePerPermissions()
        ->addCacheableDependency($entity);
    }

    if ($comment_admin instanceof AccessResultAllowed) {
      $access = AccessResult::allowed()->cachePerPermissions();
      $temp = $entity->getCommentedEntity()->access($operation, $account, TRUE);
      return ($operation != 'view') ? $access : $access->andIf($temp);
    }

    switch ($operation) {
      case 'view':
        $host_entity_access = $entity->getCommentedEntity()->access($operation, $account, TRUE);
        return AccessResult::allowedIf($this->hasPermission($entity, $account, 'access comments') && $entity->isPublished())->cachePerPermissions()->addCacheableDependency($entity)
          ->andIf($host_entity_access);

      case 'update':
        return AccessResult::allowedIf($account->id() && $account->id() == $entity->getOwnerId() && $entity->isPublished() && $this->hasPermission($entity, $account, 'edit own comments'))->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);

      case 'delete':
        return AccessResult::allowedIf($account->id() && $account->id() == $entity->getOwnerId() && $entity->isPublished() && $this->hasPermission($entity, $account, 'delete own comments'))->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);

      default:
        // No opinion.
        return AccessResult::neutral()->cachePerPermissions();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // @todo ISAICP-3013 Implement create access...
    // For now this module only works when users have global create comments
    // permissions.
    return parent::checkCreateAccess($account, $context, $entity_bundle);
  }

  /**
   * Check if user has either global or group permission.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check access for.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User account to check access with.
   * @param string $permission
   *   The permission to check.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Access object.
   */
  protected function hasPermission(EntityInterface $entity, AccountInterface $account, $permission) {
    if (!$entity instanceof CommentInterface) {
      throw new \Exception('Only comments can be handled.');
    }

    $host_entity = $entity->getCommentedEntity();
    // Is group content?
    if (!Og::isGroupContent($host_entity->getEntityTypeId(), $host_entity->bundle())) {
      return AccessResult::neutral();
    }
    // Get group.
    $group_id = $host_entity->{OgGroupAudienceHelperInterface::DEFAULT_FIELD}->first()->target_id;
    if (!$group_id) {
      return AccessResult::neutral();
    }
    /** @var \Drupal\field\Entity\FieldConfig $field_config */
    $field_config = $host_entity->{OgGroupAudienceHelperInterface::DEFAULT_FIELD}->first()->getFieldDefinition();
    /** @var \Drupal\field\Entity\FieldStorageConfig $storage_definition */
    $storage_definition = $field_config->getFieldStorageDefinition();
    $entity_type = $storage_definition->getSetting('target_type');

    $entity_storage = \Drupal::entityTypeManager()->getStorage($entity_type);
    $group = $entity_storage->load($group_id);

    /** @var \Drupal\og\OgAccessInterface $og_access */
    $og_access = \Drupal::getContainer()->get('og.access');
    return $og_access->userAccess($group, $permission, $account);
  }

}
