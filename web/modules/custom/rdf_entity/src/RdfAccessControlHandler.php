<?php

declare(strict_types = 1);

namespace Drupal\rdf_entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
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
    if ($operation === 'edit') {
      @trigger_error('Passing in the "edit" operation to RdfAccessControlHandler::checkAccess() is deprecated in RDF Entity 8.x-1.0-alpha19 and will be removed before 8.x-1.0-beta1. Pass in the "update" operation instead. See https://github.com/ec-europa/rdf_entity/issues/110', E_USER_DEPRECATED);
      $operation = 'update';
    }

    if (!$entity instanceof RdfInterface) {
      throw new \Exception('Can only handle access of Rdf entity instances.');
    }

    $entity_bundle = $entity->bundle();
    $is_owner = $account->id() === $entity->getOwnerId();

    switch ($operation) {
      case 'view':
        if ($entity->isPublished()) {
          $access_result = AccessResult::allowedIfHasPermission($account, 'view rdf entity');
        }
        else {
          $access_result = AccessResult::allowedIfHasPermission($account, 'view unpublished rdf entity');
        }

        if ($access_result instanceof RefinableCacheableDependencyInterface) {
          $access_result->addCacheableDependency($entity);
        }

        return $access_result;

      case 'update':
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
