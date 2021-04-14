<?php

namespace Drupal\sparql_entity_storage;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for SPARQL graph entities.
 */
class SparqlGraphAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $sparql_graph, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowed();

      case 'delete':
        if ($sparql_graph->id() === SparqlGraphInterface::DEFAULT) {
          return AccessResult::forbidden()->addCacheableDependency($sparql_graph);
        }
        return parent::checkAccess($sparql_graph, $operation, $account)->addCacheableDependency($sparql_graph);

      default:
        return parent::checkAccess($sparql_graph, $operation, $account);

    }
  }

}
