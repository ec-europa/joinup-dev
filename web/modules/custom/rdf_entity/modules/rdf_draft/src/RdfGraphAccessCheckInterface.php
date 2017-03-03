<?php

namespace Drupal\rdf_draft;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\Routing\Route;

/**
 * Interface RdfGraphAccessCheckInterface.
 *
 * @todo: Needs better documentation.
 *
 * @package Drupal\rdf_draft
 */
interface RdfGraphAccessCheckInterface extends AccessInterface {

  /**
   * A constant to represent the global permission for all graphs.
   */
  const VIEW_ALL_GRAPHS = 'view all graphs';

  /**
   * A custom access check.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The current route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The entity object.
   * @param string $operation
   *   The operation to check.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The result of the access check.
   */
  public function access(Route $route, AccountInterface $account, RdfInterface $rdf_entity, $operation);

  /**
   * Performs further checking if required by the access method.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param \Symfony\Component\Routing\Route $route
   *   The current route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param string $operation
   *   The operation to check.
   * @param string $graph_name
   *   The graph name.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The result of the access check.
   */
  public function checkAccess(EntityInterface $entity, Route $route, AccountInterface $account, $operation, $graph_name);

}
