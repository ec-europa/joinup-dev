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
class MenuAccess extends MenuAdminPerMenuAccess {

  /**
   * Access callback for the menu access check.
   *
   * Overrides to disallow adding new links through the UI to the front page
   * menu.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check the access for.
   * @param \Drupal\system\Entity\Menu $menu
   *   The menu to check access for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function menuLinkAddAccess(AccountInterface $account, Menu $menu): AccessResultInterface {
    if ($menu->id() === 'front-page') {
      // Disallow editing and adding new links for the front-page menu.
      return AccessResult::forbidden();
    }
    return parent::menuAccess($account, $menu);
  }

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
    // Workaround for a fatal error that is thrown in the parent method in case
    // the $menu_link_plugin parameter is empty.
    // @todo Remove this once issue #3107478 is fixed.
    // @see https://www.drupal.org/project/menu_admin_per_menu/issues/3107478
    if (empty($menu_link_content)) {
      return AccessResult::neutral();
    }

    if ($menu_link_content->getMenuName() === 'front-page') {
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
    // Workaround for a fatal error that is thrown in the parent method in case
    // the $menu_link_plugin parameter is empty.
    // @todo Remove this once issue #3107478 is fixed.
    // @see https://www.drupal.org/project/menu_admin_per_menu/issues/3107478
    if (empty($menu_link_plugin)) {
      return AccessResult::neutral();
    }

    if ($menu_link_plugin->getMenuName() === 'front-page') {
      return AccessResult::forbidden();
    }

    return parent::menuLinkAccess($account, $menu_link_plugin);
  }

}
