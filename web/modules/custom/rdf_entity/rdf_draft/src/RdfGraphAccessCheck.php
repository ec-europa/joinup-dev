<?php
namespace Drupal\rdf_draft;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\Entity\RdfEntitySparqlStorage;
use Symfony\Component\Routing\Route;

/**
 * Checks access for displaying configuration translation page.
 */
class RdfGraphAccessCheck implements AccessInterface {

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */
  public function access(AccountInterface $account, Route $route, Rdf $rdf_entity) {
    $graph = $route->getOption('graph_name');
    $entity_type_id = $route->getOption('entity_type_id');
    // @todo inject...
    $storage = \Drupal::entityManager()->getStorage($entity_type_id);
    if (!$storage instanceof RdfEntitySparqlStorage) {
      throw new \Exception('Storage not supported.');
    }
    $active_graph = $storage->getActiveGraphType();
    $storage->setActiveGraphType($graph);
    // @todo Check if user has permission to view this...
    // (fine grained permissions per graph are needed)
    if (!$account->hasPermission('view draft graph')) {
      return AccessResult::neutral();
    }

    // @todo How to do this in a generic way? Can't rely on 'rdf_entity'...
    $entity = $storage->load($rdf_entity->id());
    // Restore active graph.
    $storage->setActiveGraphType($active_graph);
    if ($entity) {
      return AccessResult::allowed();
    }
    return AccessResult::neutral();
  }

}
