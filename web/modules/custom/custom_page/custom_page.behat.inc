<?php

/**
 * @file
 * Contains \CustomPageSubContext.
 */

use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Url;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\NodeTrait;
use Drupal\joinup\Traits\OgTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\og_menu\Tests\Traits\OgMenuTrait;

/**
 * Behat step definitions for testing custom pages.
 */
class CustomPageSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use NodeTrait;
  use OgMenuTrait;
  use OgTrait;
  use RdfEntityTrait;

  /**
   * Checks if the given custom page belongs to the given collection.
   *
   * If there are multiple custom pages or collections with the same name, then
   * only the first one is checked.
   *
   * @param string $title
   *   The title of the custom page to check.
   * @param string $collection
   *   The name of the collection to check.
   *
   * @throws \Exception
   *   Thrown when a custom page with the given title doesn't exist.
   *
   * @Then the :collection collection should have a custom page titled :title
   */
  public function assertOgMembership($title, $collection) {
    $collection = $this->getRdfEntityByLabel($collection, 'collection');

    $custom_pages = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['title' => $title]);
    /** @var \Drupal\node\NodeInterface $custom_page */
    $custom_page = reset($custom_pages);

    if (empty($custom_page)) {
      throw new \Exception("The custom page titled '$title' was not found.");
    }

    /** @var \Drupal\og\Plugin\Field\FieldType\OgStandardReferenceItem $group */
    foreach ($custom_page->get(OgGroupAudienceHelperInterface::DEFAULT_FIELD) as $group) {
      if ($group->getValue()['target_id'] == $collection->id()) {
        // Test passes.
        return;
      }
    }

    throw new \Exception("The custom page '$title' is not associated with collection '{$collection->label()}'.");
  }

  /**
   * Navigates to the canonical page display of a custom page.
   *
   * @param string $title
   *   The name of the custom page.
   *
   * @When (I )go to the :title custom page
   * @When (I )visit the :title custom page
   */
  public function visitCustomPage($title) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->getNodeByTitle($title, 'custom_page');
    $this->visitPath($node->url());
  }

  /**
   * Checks the number of custom pages a collection or solution has.
   *
   * @param int $count
   *   The expected number of custom pages.
   * @param string $name
   *   The name of the collection or solution to which the custom pages belong.
   * @param string $type
   *   The RDF entity type to which the custom pages belong. Can be either
   *   'collection' or 'solution'.
   *
   * @Then the :name :type should have :count custom page(s)
   */
  public function assertContactInformationCount($count, $name, $type) {
    $group = $this->getRdfEntityByLabel($name, $type);
    $this->assertGroupContentCount($count, $group, 'node', 'custom_page');
  }

  /**
   * Checks if the user does not have access to the OG Menu administration UI.
   *
   * We only expose the OG Menu instance overview to the end user. We do not
   * expose any of the other administration pages. The adding of menu links to
   * the OG Menus is handled behind the scenes, e.g. when a custom page is
   * created a link is automatically added to the navigation menu.
   *
   * @param string $menu
   *   The name of the OG Menu instance of which the administration pages should
   *   be inaccessible.
   * @param string $title
   *   The title of the group to which the OG Menu instance belongs.
   * @param string $type
   *   The entity type of the group to which the OG Menu instance belongs.
   *
   * @throws \Exception
   *   Thrown when no OG Menu instance was found that matches the given
   *   arguments.
   *
   * @Then I should not have access to the menu link administration pages for the :menu menu of the :title :type
   */
  public function assertNoAccessToOgMenuAdministration($menu, $title, $type) {
    // Retrieve the OG Menu instance for the given menu and group.
    $group = $this->getRdfEntityByLabel($title, $type);
    $values = [
      'type' => $menu,
      OgGroupAudienceHelperInterface::DEFAULT_FIELD => $group->id(),
    ];
    $instances = \Drupal::entityTypeManager()->getStorage('ogmenu_instance')->loadByProperties($values);
    if (empty($instances)) {
      throw new \Exception("There is no $menu menu associated with the $title $type.");
    }
    /** @var \Drupal\og_menu\Entity\OgMenuInstance $instance */
    $instance = reset($instances);
    $menu = $instance->bundle();

    // Compile a list of routes to which the user should not have access.
    // Note that this excludes the 'entity.ogmenu_instance.edit_form' route to
    // which the collection facilitator should have access; this is used to
    // reorder the pages in the menu.
    $routes = [
      'entity.ogmenu_instance.canonical' => [
        'ogmenu_instance' => $instance->id(),
      ],
      'entity.ogmenu_instance.delete_form' => [
        'ogmenu_instance' => $instance->id(),
      ],
      'entity.ogmenu_instance.add_link' => [
        'ogmenu_instance' => $instance->id(),
      ],
      'entity.ogmenu_instance.create' => [
        'ogmenu' => $menu,
        'og_group_entity_type' => $group->getEntityTypeId(),
        'og_group' => $group->id(),
      ],
      'entity.ogmenu.collection' => [],
      'entity.ogmenu.add_form' => [],
      'entity.ogmenu.overview_form' => ['ogmenu' => $menu],
      'entity.ogmenu.edit_form' => ['ogmenu' => $menu],
      'entity.ogmenu.delete_form' => ['ogmenu' => $menu],
    ];

    // Check if the currently logged in user has access to these routes.
    $current_user = $this->getUserManager()->getCurrentUser();
    $user = $current_user ? \Drupal::entityTypeManager()->getStorage('user')->loadUnchanged($current_user->uid) : new AnonymousUserSession();
    foreach ($routes as $route => $parameters) {
      $url = Url::fromRoute($route, $parameters);
      if ($url->access($user)) {
        $uri = $url->toString();
        throw new \Exception("The current user has access to '$uri' in the OG Menu administration for the $menu menu of the $title $type.");
      }
    }
  }

  /**
   * Checks the number of visible items in the given OG Menu.
   *
   * @param string $menu
   *   The name of the OG Menu instance to check.
   * @param string $label
   *   The group label.
   * @param string $type
   *   The group type, either 'collection' or 'solution'.
   * @param int $count
   *   The expected number of visible menu items.
   *
   * @Then the :menu menu of the :label :type should have :count visible item(s)
   */
  public function assertVisibleItemsInMenuCount($menu, $label, $type, $count) {
    /** @var \Drupal\Core\Menu\MenuLinkTreeInterface $link_tree_service */
    $link_tree_service = \Drupal::service('menu.link_tree');

    $tree = $this->getOgMenuTreeByNameLabelAndType($menu, $label, $type);

    // Build a render array of the tree to easily get a list of enabled
    // (visible) items.
    $build = $link_tree_service->build($tree);
    $actual = !empty($build['#items']) ? count($build['#items']) : 0;

    PHPUnit_Framework_Assert::assertEquals($count, $actual);
  }

  /**
   * Checks the number of menu items in the given OG Menu instance.
   *
   * @param string $menu
   *   The name of the OG Menu instance to check.
   * @param string $label
   *   The group label.
   * @param string $type
   *   The group type, either 'collection' or 'solution'.
   * @param int $count
   *   The expected number of items in the menu.
   *
   * @Then the :menu menu of the :label :type should have :count item(s)
   */
  public function assertItemsInMenuForm($menu, $label, $type, $count) {
    $tree = $this->getOgMenuTreeByNameLabelAndType($menu, $label, $type);
    PHPUnit_Framework_Assert::assertEquals($count, count($tree));
  }

  /**
   * Enables or disables the given menu item in the given OG Menu.
   *
   * @param string $operation
   *   The operation to perform, either 'enable' or 'disable'.
   * @param string $title
   *   The title of the menu item to perform the action on.
   * @param string $menu
   *   The name of the OG Menu instance that contains the menu item.
   * @param string $label
   *   The group label.
   * @param string $type
   *   The group type, either 'collection' or 'solution'.
   *
   * @When I :operation :title in the :menu menu of the :label :type
   */
  public function switchItemInMenu($operation, $title, $menu, $label, $type) {
    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager_service */
    $menu_link_manager_service = \Drupal::service('plugin.manager.menu.link');

    // Retrieve the menu tree and transform it into a flat array of link items.
    $tree = $this->getOgMenuTreeByNameLabelAndType($menu, $label, $type);
    $tree = $this->flattenTree($tree);

    foreach ($tree as $item) {
      /** @var \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent $link */
      $link = $item->link;
      if ((string) $link->getTitle() === $title) {
        $menu_link_manager_service->updateDefinition($link->getPluginId(), [
          'enabled' => (bool) ($operation === 'enable'),
        ]);
        return;
      }
    }

    throw new \InvalidArgumentException("No menu link with title $title exists in the $menu menu for the $label $type.");
  }

  /**
   * Flattens a menu tree.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The hierarchical menu tree, as returned by MenuLinkTree::load().
   *
   * @return array
   *   The tree, flat as a plank.
   */
  protected function flattenTree(array $tree) {
    $flattened = [];
    foreach ($tree as $id => $item) {
      if ($item->count() > 1) {
        $flattened = array_merge($flattened, $this->flattenTree($item->subtree));
      }
      else {
        $flattened[$id] = $item;
      }
    }
    return $flattened;
  }

  /**
   * Resolves the given group and returns the OG Menu tree for it.
   *
   * @param string $menu_name
   *   The name of the OG Menu instance that contains the menu item.
   * @param string $group_label
   *   The group label.
   * @param string $group_type
   *   The group type, either 'collection' or 'solution'.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   The menu tree.
   */
  protected function getOgMenuTreeByNameLabelAndType($menu_name, $group_label, $group_type) {
    $group = $this->getRdfEntityByLabel($group_label, $group_type);
    $menu_instance = $this->getOgMenuInstance($group->id(), $menu_name);
    return $this->getOgMenuTree($menu_instance);
  }

}
