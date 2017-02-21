<?php

namespace Drupal\rdf_entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the rdf entity.
 */
class RdfAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * Link the activities to the permissions. checkAccess is called with the
   * $operation as defined in the routing.yml file.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if (!$entity instanceof RdfInterface) {
      throw new \Exception('Can only handle access of Rdf entity instances.');
    }

    $entity_bundle = $entity->bundle();
    $is_owner = $account->id() === $entity->getOwnerId();

    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished rdf entity');
        }
        return AccessResult::allowedIfHasPermission($account, 'view rdf entity');

      case 'edit':
        if ($account->hasPermission('edit ' . $entity_bundle . ' rdf entity')) {
          return AccessResult::allowed();
        }

        return AccessResult::allowedIf($is_owner && $account->hasPermission('edit own ' . $entity_bundle . ' rdf entity'));

      case 'delete':
        if ($account->hasPermission('delete ' . $entity_bundle . ' rdf entity')) {
          return AccessResult::allowed();
        }

        return AccessResult::allowedIf($is_owner && $account->hasPermission('delete own ' . $entity_bundle . ' rdf entity'));
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   *
   * Separate from the checkAccess because the entity does not yet exist, it
   * will be created during the 'add' process.
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    if ($entity_bundle) {
      return AccessResult::allowedIfHasPermission($account, 'create ' . $entity_bundle . ' rdf entity');
    }
    return AccessResult::allowedIfHasPermission($account, 'add rdf entity');
  }

}
