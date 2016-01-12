<?php

/**
 * @file
 * Contains \Drupal\collection\CollectionAccessControlHandler.
 */

namespace Drupal\collection;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Collection entity.
 *
 * @see \Drupal\collection\Entity\Collection.
 */
class CollectionAccessControlHandler extends EntityAccessControlHandler {
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(CollectionInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished collection entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published collection entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit collection entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete collection entities');
    }

    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add collection entities');
  }

}
