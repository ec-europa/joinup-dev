<?php

declare(strict_types = 1);

namespace Drupal\joinup_core;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Service to check if changing workflow states is permitted for a given user.
 */
class WorkflowStatePermission implements WorkflowStatePermissionInterface {

  /**
   * The workflow state permission plugin manager.
   *
   * @var \Drupal\joinup_core\WorkflowStatePermissionPluginManager
   */
  protected $pluginManager;

  /**
   * Constructs a WorkflowStatePermission service.
   *
   * @param \Drupal\joinup_core\WorkflowStatePermissionPluginManager $pluginManager
   *   The workflow state permission plugin manager.
   */
  public function __construct(WorkflowStatePermissionPluginManager $pluginManager) {
    $this->pluginManager = $pluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public function isStateUpdatePermitted(AccountInterface $account, EntityInterface $entity, string $from_state, string $to_state): bool {
    foreach ($this->pluginManager->getDefinitions() as $definition) {
      /** @var \Drupal\joinup_core\WorkflowStatePermissionPluginInterface $plugin */
      $plugin = $this->pluginManager->createInstance($definition['id']);
      if ($plugin->applies($entity)) {
        $result = $plugin->isStateUpdatePermitted($account, $entity, $from_state, $to_state);
        // Stop iterating as soon as any plugin denies access.
        if (!$result) {
          return FALSE;
        }
      }
    }
    return TRUE;
  }

}
