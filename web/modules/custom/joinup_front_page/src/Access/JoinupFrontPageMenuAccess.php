<?php

declare(strict_types = 1);

namespace Drupal\joinup_front_page\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\menu_admin_per_menu\Access\MenuAdminPerMenuAccess;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\system\Entity\Menu;

/**
 * Checks access for displaying administer menu pages.
 */
class JoinupFrontPageMenuAccess extends MenuAdminPerMenuAccess {

  /**
   * {@inheritdoc}
   */
  public function menuAccess(AccountInterface $account, Menu $menu): AccessResultInterface {
    if ($menu->id() === 'front-page') {
      // Disallow adding new links for the front-page menu.
      return AccessResult::forbidden();
    }
    return parent::menuAccess($account, $menu);
  }

  /**
   * {@inheritdoc}
   */
  public function menuLinkAccess(AccountInterface $account, MenuLinkInterface $menu_link_plugin = NULL): AccessResultInterface {
    if ($menu_link_plugin->getMenuName() === 'front-page') {
      // Disallow editing new links for the front-page menu.
      return AccessResult::forbidden();
    }
    return parent::menuLinkAccess($account, $menu_link_plugin);
  }

  /**
   * {@inheritdoc}
   */
  public function menuItemAccess(AccountInterface $account, MenuLinkContent $menu_link_content = NULL): AccessResultInterface {
    if ($menu_link_content->getMenuName() === 'front-page') {
      // Disable canonical page access.
      return AccessResult::forbidden();
    }
    return parent::menuItemAccess($account, $menu_link_content);
  }

}
