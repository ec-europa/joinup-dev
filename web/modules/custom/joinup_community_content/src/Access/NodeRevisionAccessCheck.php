<?php

namespace Drupal\joinup_community_content\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Access\NodeRevisionAccessCheck as CoreNodeRevisionAccessCheck;
use Drupal\node\NodeInterface;
use Drupal\og\GroupTypeManager;
use Drupal\og\OgAccessInterface;
use Symfony\Component\Routing\Route;

/**
 * Extends the core node revision access check by taking into account og roles.
 */
class NodeRevisionAccessCheck extends CoreNodeRevisionAccessCheck {

  /**
   * The OG group manager.
   *
   * @var \Drupal\og\GroupTypeManager
   */
  protected $groupTypeManager;

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * A static cache of og access checks.
   *
   * @var array
   */
  protected $ogAccessCache = [];

  /**
   * Constructs a new NodeRevisionAccessCheck.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\og\GroupTypeManager $group_type_manager
   *   The OG group manager.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   */
  public function __construct(EntityManagerInterface $entity_manager, GroupTypeManager $group_type_manager, OgAccessInterface $og_access) {
    parent::__construct($entity_manager);

    $this->groupTypeManager = $group_type_manager;
    $this->ogAccess = $og_access;
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, AccountInterface $account, $node_revision = NULL, NodeInterface $node = NULL) {
    if ($node_revision) {
      $node = $this->nodeStorage->loadRevision($node_revision);
    }
    $operation = $route->getRequirement('_access_node_revision');

    // Check og access.
    // fallback.
    $og_access = $this->checkOgAccess($node, $account, $operation);

    if (!$og_access->isNeutral()) {
      return $og_access;
    }

    // Verify caching.
    return AccessResult::allowedIf($node && $this->checkAccess($node, $account, $operation))->cachePerPermissions()->addCacheableDependency($node);
  }

  /**
   * Checks node revision access against og roles and their permissions.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user object representing the user for whom the operation is to be
   *   performed.
   * @param string $operation
   *   (optional) The specific operation being checked. Defaults to 'view'.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function checkOgAccess(NodeInterface $node, AccountInterface $account, $operation) {
    if (!$this->groupTypeManager->isGroupContent('node', $node->bundle())) {
      return AccessResult::neutral();
    }

    $map = [
      'view' => 'view all revisions',
      'update' => 'revert all revisions',
      'delete' => 'delete all revisions',
    ];
    $bundle = $node->bundle();
    $type_map = [
      'view' => "view $bundle revisions",
      'update' => "revert $bundle revisions",
      'delete' => "delete $bundle revisions",
    ];

    // If the operation is not supported, we return a neutral access result
    // so that the default access check can take place.
    if (!isset($map[$operation]) || !isset($type_map[$operation])) {
      return AccessResult::neutral()->addCacheableDependency($node);
    }

    // There should be at least two revisions. If the vid of the given node
    // and the vid of the default revision differ, then we already have two
    // different revisions so there is no need for a separate database check.
    // Also, if you try to revert to or delete the default revision, that's
    // not good.
    // @see \Drupal\node\Access\NodeRevisionAccessCheck::checkAccess()
    if ($node->isDefaultRevision() && ($this->nodeStorage->countDefaultLanguageRevisions($node) == 1 || $operation == 'update' || $operation == 'delete')) {
      return AccessResult::forbidden()->addCacheableDependency($node);
    }

    // Check that the user has either:
    // - the global ("<operation> all permissions") permission;
    // - the bundle specific permission;
    // - the "administer nodes" permission.
    /** @var \Drupal\Core\Access\AccessResultInterface $result */
    $result = $this->nodeAccess->access($node, $map[$operation], $account, TRUE)
      ->orIf($this->nodeAccess->access($node, $type_map[$operation], $account, TRUE))
      ->orIf(AccessResult::allowedIf($account->hasPermission('administer nodes')));

    // If the result is either neutral or forbidden, the user doesn't have the
    // needed permissions so quit.
    if (!$result->isAllowed()) {
      return $result;
    }

    // First check the access to the default revision and finally, if the
    // node passed in is not the default revision then access to that, too.
    $result = $result->andIf($this->nodeAccess->access($this->nodeStorage->load($node->id()), $operation, $account, TRUE));
    if (!$node->isDefaultRevision()) {
      $result = $result->andIf($this->nodeAccess->access($node, $operation, $account, TRUE));
    }

    return $result;
  }

}
