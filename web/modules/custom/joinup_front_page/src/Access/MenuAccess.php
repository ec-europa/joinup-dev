<?php

declare(strict_types = 1);

namespace Drupal\joinup_front_page\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\menu_admin_per_menu\Access\MenuAdminPerMenuAccess;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Checks access for displaying administer menu pages.
 */
class MenuAccess extends MenuAdminPerMenuAccess {

  /**
   * Provides access control for the menu content entity edit link.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent|null $menu_link_content
   *   The menu link item.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function menuLinkItemEditAccess(AccountInterface $account, MenuLinkContent $menu_link_content = NULL): AccessResultInterface {
    if (!empty($menu_link_content) && $menu_link_content->getMenuName() === 'front-page') {
      return AccessResult::forbidden();
    }
    return parent::menuItemAccess($account, $menu_link_content);
  }

  /**
   * Provides access control for the menu plugin edit link.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   * @param \Drupal\Core\Menu\MenuLinkInterface|null $menu_link_plugin
   *   The menu link plugin.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function menuLinkPluginEditAccess(AccountInterface $account, MenuLinkInterface $menu_link_plugin = NULL): AccessResultInterface {
    if (empty($menu_link_plugin)) {
      return AccessResult::neutral();
    }
    if ($menu_link_plugin->getMenuName() === 'front-page') {
      return AccessResult::forbidden();
    }
    return parent::menuLinkAccess($account, $menu_link_plugin);
  }

}
