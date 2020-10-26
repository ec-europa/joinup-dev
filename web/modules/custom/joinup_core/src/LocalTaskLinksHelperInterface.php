<?php

declare(strict_types = 1);

namespace Drupal\joinup_core;

use Drupal\Core\Access\AccessResultInterface;

/**
 * Provides an interface for 'joinup_core.local_task_links_helper' service.
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
