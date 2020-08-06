<?php

declare(strict_types = 1);

namespace Drupal\joinup_community_content\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * The configuration factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
   * Constructs a new NodeRevisionAccessCheck.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\og\GroupTypeManager $group_type_manager
   *   The OG group manager.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, GroupTypeManager $group_type_manager, OgAccessInterface $og_access, ConfigFactoryInterface $config_factory) {
    parent::__construct($entity_type_manager);

    $this->groupTypeManager = $group_type_manager;
    $this->ogAccess = $og_access;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, AccountInterface $account, $node_revision = NULL, ?NodeInterface $node = NULL) {
    if ($node_revision) {
      $node = $this->nodeStorage->loadRevision($node_revision);
    }
    $operation = $route->getRequirement('_access_node_revision');

    $og_access = $this->checkOgAccess($node, $account, $operation);
    // If we have already an opinion, return it.
    if (!$og_access->isNeutral()) {
      return $og_access;
    }

    $global_access = AccessResult::allowedIf($node && $this->checkAccess($node, $account, $operation));
    return $global_access->cachePerPermissions()->addCacheableDependency($node)->inheritCacheability($og_access);
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
  public function checkOgAccess(NodeInterface $node, AccountInterface $account, $operation) {
    if (!$this->groupTypeManager->isGroupContent('node', $node->bundle())) {
      return AccessResult::neutral();
    }

    // Map entity operations to group level permissions.
    $map = [
      'view' => 'view all revisions',
    ];
    $bundle = $node->bundle();
    $type_map = [
      'view' => "view $bundle revisions",
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

    // The global "administer nodes" permissions gives full access to revisions.
    // @see parent::checkAccess()
    if ($account->hasPermission('administer nodes')) {
      return AccessResult::allowed()->cachePerPermissions()->addCacheableDependency($node);
    }

    // Check if the user has either the "all" or the type-specific permission.
    // We cannot use orIf() to join them, as OG returns access denied when the
    // permission is not present for the user in a group, and orIf() returns
    // forbidden if any of the parameters is forbidden.
    $all_access = $this->ogAccess->userAccessEntity($map[$operation], $node, $account);
    $type_access = $this->ogAccess->userAccessEntity($type_map[$operation], $node, $account);
    // If neither of the access checks are allowed, check the node_access_strict
    // configuration and return either neutral or forbidden.
    // @see og_entity_access()
    if (!$all_access->isAllowed() && !$type_access->isAllowed()) {
      $node_access_strict = $this->configFactory->get('og.settings')->get('node_access_strict');

      return AccessResult::forbiddenIf($node_access_strict)->inheritCacheability($all_access)->inheritCacheability($type_access);
    }

    // Merge all the cacheability of the two permissions checked, as they might
    // differ.
    $result = AccessResult::allowed()->inheritCacheability($all_access)->inheritCacheability($type_access);

    // First check the access to the default revision and finally, if the
    // node passed in is not the default revision then access to that, too.
    $result = $result->andIf($this->nodeAccess->access($this->nodeStorage->load($node->id()), $operation, $account, TRUE));
    if (!$node->isDefaultRevision()) {
      $result = $result->andIf($this->nodeAccess->access($node, $operation, $account, TRUE));
    }

    return $result;
  }

}
