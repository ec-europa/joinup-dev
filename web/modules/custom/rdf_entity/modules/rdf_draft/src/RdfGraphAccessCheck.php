<?php

declare(strict_types = 1);

namespace Drupal\rdf_draft;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\rdf_entity\RdfInterface;
use Drupal\sparql_entity_storage\SparqlEntityStorage;
use Drupal\sparql_entity_storage\SparqlGraphInterface;
use Symfony\Component\Routing\Route;

/**
 * Checks access for displaying configuration translation page.
 */
class RdfGraphAccessCheck implements RdfGraphAccessCheckInterface {
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Constructs a new access checker instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, AccountInterface $account, RdfInterface $rdf_entity, $operation = 'view') {
    $graph = $route->getOption('graph_name');
    $entity_type_id = $route->getOption('entity_type_id');
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    if (!$storage instanceof SparqlEntityStorage) {
      throw new \Exception('Storage not supported.');
    }

    // The active graph is the published graph. It is handled by the default
    // operation handler.
    $default_graph = $storage->getGraphHandler()->getBundleGraphUri($rdf_entity->getEntityTypeId(), $rdf_entity->bundle(), SparqlGraphInterface::DEFAULT);
    $requested_graph = $storage->getGraphHandler()->getBundleGraphUri($rdf_entity->getEntityTypeId(), $rdf_entity->bundle(), $graph);
    if ($requested_graph == $default_graph) {
      return AccessResult::neutral();
    }

    // Check if there is an entity saved in the passed graph.
    $entity = $storage->load($rdf_entity->id(), [$graph]);

    // @todo When the requested graph is the only one and it is not the
    // default, it is loaded in the default view, so maybe there is no need
    // to also show a separate tab.
    return AccessResult::allowedIf($entity && $this->checkAccess($rdf_entity, $route, $account, $operation, $graph))->cachePerPermissions()->addCacheableDependency($rdf_entity);
  }

  /**
   * {@inheritdoc}
   */
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

    // @todo This probably needs to be cached manually creating a cid.
    // @see: \Drupal\node\Access\NodeRevisionAccessCheck::checkAccess().
    // @todo This needs also to check cache for cached permission.
    // @see: \Drupal\Core\Entity\EntityAccessControlHandler::access().
    $has_permission = $account->hasPermission($map[$operation]) || $account->hasPermission($type_map[$operation]);
    $access = $has_permission ? AccessResult::allowed() : AccessResult::neutral();
    $arguments = [$entity, $operation, $account, $graph_name];
    $access_array = array_merge(
      [$access],
      $this->moduleHandler->invokeAll('entity_graph_access', $arguments),
      $this->moduleHandler->invokeAll($entity_type_id . '_graph_access', $arguments)
    );

    $return = $this->processAccessHookResults($access_array);
    return $return->isAllowed();
  }

  /**
   * Processes access results.
   *
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
