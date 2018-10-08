<?php

declare(strict_types = 1);

namespace Drupal\tallinn;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Interface for dashboard access service.
 */
interface DashboardAccessInterface {

  /**
   * Checks the access to the dashboard functionality for a given user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to be checked.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account): AccessResultInterface;

}
