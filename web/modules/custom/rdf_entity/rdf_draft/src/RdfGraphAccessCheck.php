<?php

namespace Drupal\rdf_draft;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\rdf_entity\Entity\RdfEntitySparqlStorage;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\Routing\Route;

/**
 * Checks access for displaying configuration translation page.
 */
class RdfGraphAccessCheck implements RdfGraphAccessCheckInterface  {
  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Constructs a EntityCreateAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
    // @todo: EntityHandlerBase is not injecting this service. Why?
    $this->moduleHandler = \Drupal::moduleHandler();
  }

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *    Run access checks for this account.
   * @param \Symfony\Component\Routing\Route $route
   *    The current route.
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *    The entity object.
   * @param string $operation
   *    The operation to check.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *    The result of the access check.
   *
   * @throws \Exception
   *    Thrown when the storage does not support graphs.
   */
  public function access(Route $route, AccountInterface $account, RdfInterface $rdf_entity, $operation = 'view') {
    $graph = $route->getOption('graph_name');
    $entity_type_id = $route->getOption('entity_type_id');
    $storage = $this->entityManager->getStorage($entity_type_id);
    if (!$storage instanceof RdfEntitySparqlStorage) {
      throw new \Exception('Storage not supported.');
    }

    // The active graph is the published graph. It is handled by the default
    // operation handler.
    // @todo: getActiveGraph is not the default. We should load from settings.
    $default_graph = $storage->getBundleGraphUri($rdf_entity->bundle(), 'default');
    $requested_graph = $storage->getBundleGraphUri($rdf_entity->bundle(), $graph);
    if ($requested_graph == $default_graph) {
      return AccessResult::neutral();
    }

    $active_graph_type = $storage->getActiveGraphType();
    // Check if there is an entity saved in the passed graph.
    $storage->setActiveGraphType([$graph]);
    $entity = $storage->load($rdf_entity->id());
    // Restore active graph.
    $storage->setActiveGraphType($active_graph_type);

    // @todo: When the requested graph is the only one and it is not the
    //    default, it is loaded in the default view, so maybe there is no need
    //    to also show a separate tab.
    return AccessResult::allowedIf($entity && $this->checkAccess($rdf_entity, $route, $account, $operation, $graph))->cachePerPermissions()->addCacheableDependency($rdf_entity);
  }

  public function checkAccess(EntityInterface $entity, Route $route, AccountInterface $account, $operation, $graph_name) {
    if (!$entity) {
      return FALSE;
    }

    // For now, we only have the view operation but this is not the only
    // operation so we will check anyway.
    $map = ['view' => 'view all graphs'];
    $entity_type_id = $entity->getEntityTypeId();
    $type_map = ['view' => "view $entity_type_id $graph_name graph"];

    // If the operation is not supported, do not allow access.
    if (!isset($map[$operation]) || !isset($type_map[$operation])) {
      return FALSE;
    }

    // @todo: This probably needs to be cached manually creating a cid.
    // @see: \Drupal\node\Access\NodeRevisionAccessCheck::checkAccess().
    // @todo: This needs also to check cache for cached permission.
    // @see: \Drupal\Core\Entity\EntityAccessControlHandler::access().
    $has_permission = $account->hasPermission($map[$operation]) || $account->hasPermission($type_map[$operation]);
    $access = $has_permission ? AccessResult::allowed() : AccessResult::neutral();

    $access_array = array_merge(
      [$access],
      $this->moduleHandler->invokeAll('entity_graph_access', [$entity, $operation, $account, $graph_name]),
      $this->moduleHandler->invokeAll($entity_type_id . '_graph_access', [$entity, $operation, $account, $graph_name])
    );

    $return = $this->processAccessHookResults($access_array);
    return $return->isAllowed();
  }

  /**
   * We grant access to the entity if both of these conditions are met:
   * - No modules say to deny access.
   * - At least one module says to grant access.
   *
   * @param \Drupal\Core\Access\AccessResultInterface[] $access
   *   An array of access results of the fired access hook.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The combined result of the various access checks' results. All their
   *   cacheability metadata is merged as well.
   *
   * @see \Drupal\Core\Access\AccessResultInterface::orIf()
   * @see \Drupal\Core\Entity\EntityAccessControlHandler::processAccessHookResults()
   */
  protected function processAccessHookResults(array $access) {
    // No results means no opinion.
    if (empty($access)) {
      return AccessResult::neutral();
    }

    $result = array_shift($access);
    foreach ($access as $other) {
      $result = $result->orIf($other);
    }
    return $result;
  }

}
