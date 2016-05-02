<?php

namespace Drupal\joinup\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Only grants access to UID 1, denies everyone else.
 */
class Uid1OnlyAccess implements AccessInterface {

  /**
   * Grants access only to UID 1.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    if ($account->id() == 1) {
      return AccessResult::allowed()->addCacheContexts(['user']);
    }
    return AccessResult::forbidden()->addCacheContexts(['user']);
  }

}
