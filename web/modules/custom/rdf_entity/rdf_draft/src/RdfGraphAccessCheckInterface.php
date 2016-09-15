<?php

namespace Drupal\rdf_draft;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\Routing\Route;

interface RdfGraphAccessCheckInterface extends AccessInterface {

  const VIEW_ALL_GRAPHS = 'view all graphs';

  public function access(Route $route, AccountInterface $account, RdfInterface $rdf_entity);

  public function checkAccess(EntityInterface $entity, Route $route, AccountInterface $account, $operation, $graph_name);
}