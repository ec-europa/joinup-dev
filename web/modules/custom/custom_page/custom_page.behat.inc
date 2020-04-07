<?php

/**
 * @file
 * Contains \CustomPageSubContext.
 */

declare(strict_types = 1);

use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Url;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope;
use Drupal\joinup\Traits\FileTrait;
use Drupal\joinup\Traits\NodeTrait;
use Drupal\joinup\Traits\OgTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\joinup\Traits\TraversingTrait;
use Drupal\joinup\Traits\UtilityTrait;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\og_menu\Tests\Traits\OgMenuTrait;
use PHPUnit\Framework\Assert;

/**
 * Behat step definitions for testing custom pages.
 */
class CustomPageSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use FileTrait;
  use NodeTrait;
  use OgMenuTrait;
  use OgTrait;
  use RdfEntityTrait;
  use UtilityTrait;
  use TraversingTrait;

  /**
   * Handles the attachment field for custom page nodes.
   *
   * @param \Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope $scope
   *   An object containing the entity properties and fields that are to be used
   *   for creating the node as properties on the object.
   *
   * @throws \Exception
   *   Thrown when a file being attached is not found.
   *
   * @BeforeNodeCreate
   */
  public static function massageCustomPageFieldsBeforeNodeCreate(BeforeNodeCreateScope $scope): void {
    /** @var \stdClass $node */
    $node = $scope->getEntity();

    if ($node->type !== 'custom_page') {
      return;
    }

    if (!empty($node->logo)) {
      // We want to copy it from the fixtures into the file system and register
      // it in the CustomPageSubContext so it can be cleaned up after the
      // scenario ends. Perform a small dance to get access to the context class
      // from inside this static callback.
      /** @var \Behat\Behat\Context\Environment\InitializedContextEnvironment $environment */
      $environment = $scope->getEnvironment();
      /** @var \CustomPageSubContext $context */
      $context = $environment->getContext(self::class);

      /** @var \Drupal\file\FileInterface $file */
      $file = $context->createFile($node->logo);
      $node->logo = $file->getFileUri();
    }
  }

  /**
   * Navigates to the canonical page display of a custom page.
   *
   * @param string $title
   *   The name of the custom page.
   *
   * @When I go to the :title custom page
   * @When I visit the :title custom page
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function visitCustomPage(string $title): void {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->getNodeByTitle($title, 'custom_page');
    $this->visitPath($node->toUrl()->toString());
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
   * @throws \Exception
   *   Thrown when the count is incorrect.
   *
   * @Then the :name :type should have :count custom page(s)
   */
  public function assertContactInformationCount(int $count, string $name, string $type): void {
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
  public function assertNoAccessToOgMenuAdministration(string $menu, string $title, string $type): void {
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
    // which the facilitator should have access; this is used to reorder the
    // pages in the menu.
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
      'entity.ogmenu.solution' => [],
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
  public function assertVisibleItemsInMenuCount(string $menu, string $label, string $type, int $count): void {
    /** @var \Drupal\Core\Menu\MenuLinkTreeInterface $link_tree_service */
    $link_tree_service = \Drupal::service('menu.link_tree');

    $tree = $this->getOgMenuTreeByNameLabelAndType($menu, $label, $type);

    // Build a render array of the tree to easily get a list of enabled
    // (visible) items.
    $build = $link_tree_service->build($tree);
    $actual = !empty($build['#items']) ? count($build['#items']) : 0;

    Assert::assertEquals($count, $actual);
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
  public function assertItemsInMenuForm(string $menu, string $label, string $type, int $count): void {
    $tree = $this->getOgMenuTreeByNameLabelAndType($menu, $label, $type);
    Assert::assertEquals($count, count($tree));
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
  public function switchItemInMenu(string $operation, string $title, string $menu, string $label, string $type): void {
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
  protected function flattenTree(array $tree): array {
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
  protected function getOgMenuTreeByNameLabelAndType(string $menu_name, string $group_label, string $group_type): array {
    $group = $this->getRdfEntityByLabel($group_label, $group_type);
    $menu_instance = $this->getOgMenuInstance($group->id(), $menu_name);
    return $this->getOgMenuTree($menu_instance);
  }

  /**
   * Changes the publication state of a given custom page.
   *
   * Note that this is only targeting custom pages because most other node types
   * are community content whose workflow state controls if they are published.
   *
   * @param string $title
   *   The title of the custom page to update.
   * @param string $state
   *   The publication state to set, can be either 'published' or 'unpublished'.
   *
   * @throws \Exception
   *   Thrown when the custom page doesn't exist, or cannot be saved.
   *
   * @When the publication state of the :title custom page is changed to :state
   */
  public function updatePublicationState(string $title, string $state): void {
    $node = $this->getNodeByTitle($title, 'custom_page');
    if ($state === 'published') {
      $node->setPublished();
    }
    else {
      $node->setUnpublished();
    }
    $node->save();
  }

  /**
   * Asserts that a custom page tile has the logo properly set.
   *
   * @param string $title
   *   The title of the custom page tile to inspect.
   *
   * @throws \Exception
   *   Thrown when the logo is not found or not correct.
   *
   * @Then the logo should be shown in the :title custom page tile
   */
  public function assertCustomPageTileHasLogo(string $title): void {
    // Retrieve the URL of the logo in the tile.
    $tile = $this->getTileByHeading($title);
    $actual_url = $tile->find('xpath', '//img')->getAttribute('src');
    if (!$actual_url) {
      throw new \Exception("No logo link found in the '$title' tile.");
    }

    // Retrieve the image entity from the custom page logo.
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->getNodeByTitle($title, 'custom_page');
    /** @var \Drupal\file\FileInterface $image */
    $image = \Drupal::service('file_url.handler')::urlToFile($node->get('field_custom_page_logo')->first()->target_id);

    // Generate the expected URL of the logo using the right image style.
    // @see \Drupal\image\Plugin\Field\FieldFormatter\ImageUrlFormatter::viewElements()
    /** @var \Drupal\image\ImageStyleInterface $image_style */
    $image_style = \Drupal::entityTypeManager()->getStorage('image_style')->load('horizontal_medium_image');
    $expected_url = file_url_transform_relative($image_style->buildUrl($image->getFileUri()));

    Assert::assertEquals($expected_url, $actual_url, 'The logo shown is not the correct one.');
  }

}
