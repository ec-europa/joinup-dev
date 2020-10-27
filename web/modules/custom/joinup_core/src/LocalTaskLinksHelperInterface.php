<?php

declare(strict_types = 1);

namespace Drupal\joinup_core;

use Drupal\Core\Access\AccessResultInterface;

/**
 * Provides an interface for 'joinup_core.local_task_links_helper' service.
 *
 * Local tasks can be displayed in two ways:
 * - As horizontal tabs: for example see user/login and user/password.
 * - As a three dots menu: for example a collection page viewed as facilitator.
 *
 * By default the local tasks are displayed as a three dots menu. If a route
 * needs to show them as horizontal tasks, this can be achieved by adding the
 * route to the `block.block.horizontal_tabs` block.
 */
interface LocalTaskLinksHelperInterface {

  /**
   * Checks whether horizontal tabs can be accessed on the current page.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Whether horizontal tabs can be accessed on the current page.
   */
  public function allowHorizontalTabs(): AccessResultInterface;

  /**
   * Checks whether the three-dots menu can be accessed on the current page.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Whether the three-dots menu can be accessed on the current page.
   */
  public function allowThreeDotsMenu(): AccessResultInterface;

}
