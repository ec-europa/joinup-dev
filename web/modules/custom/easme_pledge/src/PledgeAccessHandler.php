<?php

declare(strict_types = 1);

namespace Drupal\easme_pledge;

use Drupal\joinup_community_content\CommunityContentWorkflowAccessControlHandler;

/**
 * Access handler for the Pledge content bundle.
 */
class PledgeAccessHandler extends CommunityContentWorkflowAccessControlHandler {

  /**
   * Returns the configured permission scheme for the given operation.
   *
   * @param string $operation
   *   The operation for which to return the permission scheme. Can be one of
   *   'create', 'view', 'update', 'delete'.
   *
   * @return array
   *   The permission scheme.
   */
  protected function getPermissionScheme(string $operation): array {
    \assert(\in_array($operation, ['create', 'view', 'update', 'delete']), 'A valid operation should be passed');
    return $this->configFactory->get('easme_pledge.permission_scheme')->get($operation);
  }

}
