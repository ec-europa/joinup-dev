<?php

declare(strict_types = 1);

namespace Drupal\custom_page\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Menu\InaccessibleMenuLink;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\custom_page\CustomPageOgMenuLinksManagerInterface;
use Drupal\og_menu\OgMenuInstanceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block with the outline of a table of content in a custom page.
 *
 * @Block(
 *  id = "toc_outline",
 *  admin_label = @Translation("Table of contents outline"),
 *  category = @Translation("Custom page"),
 *  context = {
 *    "og" = @ContextDefinition("entity", label = @Translation("Group")),
 *    "node" = @ContextDefinition("entity", label = @Translation("Custom page")),
 *  },
 * )
 */
class TableOfContentsOutline extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The og menu manager for custom pages.
   *
   * @var \Drupal\custom_page\CustomPageOgMenuLinksManagerInterface
   */
  protected $ogMenuManager;

  /**
   * The menu tree manager service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * The active trail service.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $activeTrail;

  /**
   * The active link.
   *
   * @var \Drupal\Core\Menu\MenuLinkInterface|null
   */
  protected $activeLink;

  /**
   * The flattened OG menu .
   *
   * @var \Drupal\Core\Menu\MenuLinkInterface[]
   */
  protected $flattenedMenu = [];

  /**
   * The og menu instance.
   *
   * @var \Drupal\og_menu\OgMenuInstanceInterface
   */
  protected $ogMenuInstance;

  /**
   * Constructs a new RecommendedContentBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\custom_page\CustomPageOgMenuLinksManagerInterface $og_menu_manager
   *   The og menu manager for custom pages.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_link_tree
   *   The menu tree manager service.
   * @param \Drupal\Core\Menu\MenuActiveTrailInterface $active_trail
   *   The active trail service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CustomPageOgMenuLinksManagerInterface $og_menu_manager, MenuLinkTreeInterface $menu_link_tree, MenuActiveTrailInterface $active_trail) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->ogMenuManager = $og_menu_manager;
    $this->menuLinkTree = $menu_link_tree;
    $this->activeTrail = $active_trail;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('custom_page.og_menu_links_manager'),
      $container->get('menu.link_tree'),
      $container->get('menu.active_trail'),
      $container->get('menu.tree_storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    // In case no active link is detected, escape early as there are no relative
    // links to the menu.
    if (empty($this->getActiveLink())) {
      return [];
    }

    $links = [];
    if ($prev = $this->getPrevElement()) {
      $links['prev'] = [
        'url' => $prev->getUrlObject(),
        'title' => Markup::create('<span class="icon icon--previous"></span>' . $prev->getTitle()),
      ];
    }
    if ($up = $this->getParentElement()) {
      $links['up'] = [
        'url' => $up->getUrlObject(),
        'title' => $this->t('Up'),
      ];
    }
    if ($next = $this->getNextElement()) {
      $links['next'] = [
        'url' => $next->getUrlObject(),
        'title' => Markup::create($next->getTitle() . '<span class="icon icon--next"></span>'),
      ];
    }

    $build = [
      '#theme' => 'links',
      '#links' => $links,
    ];

    $og_menu_id = $this->getOgMenuName();
    $system_menu_tags = Cache::buildTags('config:system.menu', [$og_menu_id], '.');
    $og_menu_instance_tags = Cache::buildTags('ogmenu_instance', [$this->getOgMenuInstance()->id()]);
    $build['#cache']['tags'] = Cache::mergeTags($system_menu_tags, $og_menu_instance_tags);

    return $build;
  }

  /**
   * Returns the previous element related to the active link.
   *
   * @return \Drupal\Core\Menu\MenuLinkInterface|null
   *   The previous menu link element or null if no element is found.
   */
  protected function getPrevElement(): ?MenuLinkInterface {
    return $this->getSibling(-1);
  }

  /**
   * Returns the next element related to the active link.
   *
   * @return \Drupal\Core\Menu\MenuLinkInterface|null
   *   The next menu link.
   */
  protected function getNextElement(): ?MenuLinkInterface {
    return $this->getSibling(1);
  }

  /**
   * Returns a sibling element related to the active link.
   *
   * To get the correct element we will use the flattened list in which holds
   * the natural prev/next list.
   *
   * @param int $increment
   *   The variation.
   *
   * @return \Drupal\Core\Menu\MenuLinkInterface|null
   *   The sibling menu link.
   */
  protected function getSibling(int $increment): ?MenuLinkInterface {
    $flattened_menu = $this->getFlattenedMenu();
    $active_link_id = $this->getActiveLink()->getPluginId();
    $index = array_keys($flattened_menu);
    $active_link_delta = array_search($active_link_id, $index);
    return isset($index[$active_link_delta + $increment]) ? $flattened_menu[$index[$active_link_delta + $increment]] : NULL;
  }

  /**
   * Returns the parent element related to the active link.
   *
   * @return \Drupal\Core\Menu\MenuLinkInterface|null
   *   The parent link tree.
   */
  protected function getParentElement(): ?MenuLinkInterface {
    $trail = $this->activeTrail->getActiveTrailIds($this->getOgMenuName());

    // Remove the current element.
    array_shift($trail);
    // Pickup the parent plugin ID.
    $parent_plugin_id = key($trail);

    return $parent_plugin_id ? $this->getFlattenedMenu()[$parent_plugin_id] : NULL;
  }

  /**
   * Loads and returns the OG menu instance.
   *
   * @return \Drupal\og_menu\OgMenuInstanceInterface|null
   *   The OG menu instance.
   */
  protected function getOgMenuInstance(): ?OgMenuInstanceInterface {
    if (empty($this->ogMenuInstance)) {
      /** @var \Drupal\node\NodeInterface $custom_page */
      $custom_page = $this->getContext('node')->getContextData()->getValue();
      $this->ogMenuInstance = $this->ogMenuManager->getOgMenuInstanceByCustomPage($custom_page);
    }
    return $this->ogMenuInstance;
  }

  /**
   * Returns the active link.
   *
   * @return \Drupal\Core\Menu\MenuLinkInterface|null
   *   The active link.
   */
  protected function getActiveLink(): ?MenuLinkInterface {
    if (empty($this->activeLink)) {
      $og_menu_id = $this->getOgMenuName();
      $this->activeLink = $this->activeTrail->getActiveLink($og_menu_id);
    }
    return $this->activeLink;
  }

  /**
   * Returns the menu tree of the current og menu.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   The og menu tree.
   */
  protected function getMenuTree(): array {
    // Get the topmost custom page link plugin ID.
    $trail = $this->activeTrail->getActiveTrailIds($this->getOgMenuName());
    // Remove the empty root element.
    array_pop($trail);
    // Grab the root page menu link.
    $root_link = array_pop($trail);

    $menu_tree_parameters = (new MenuTreeParameters())
      ->setRoot($root_link)
      ->onlyEnabledLinks();

    $tree = $this->menuLinkTree->load($this->getOgMenuName(), $menu_tree_parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];

    return $this->menuLinkTree->transform($tree, $manipulators);
  }

  /**
   * Retrieves the og menu in a flattened list.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   The flattened menu.
   */
  protected function getFlattenedMenu(): array {
    if (empty($this->flattenedMenu)) {
      $tree = $this->getMenuTree();
      $this->flatOutlineTree($tree);

      // Inaccessible links are still returned but as instance of
      // Drupal\Core\Menu\InaccessibleMenuLink. Strip off these links from here.
      $this->flattenedMenu = array_filter($this->flattenedMenu, function (MenuLinkInterface $menu_link): bool {
        return !($menu_link instanceof InaccessibleMenuLink);
      });
    }
    return $this->flattenedMenu;
  }

  /**
   * Recursively converts a tree of menu links to a flat array.
   *
   * Inspired from the book module and the DefaultMenuLinkTreeManipulators
   * class.
   *
   * @param array $tree
   *   A tree of menu links in an array.
   *
   * @see \Drupal\book\BookManager::flatBookTree
   * @see \Drupal\Core\Menu\DefaultMenuLinkTreeManipulators::flatten
   */
  protected function flatOutlineTree(array $tree): void {
    /** @var \Drupal\Core\Menu\MenuLinkTreeElement[] $tree */
    foreach ($tree as $menu_link_id => $data) {
      $this->flattenedMenu[$data->link->getPluginId()] = $data->link;
      if ($data->subtree) {
        $this->flatOutlineTree($data->subtree);
      }
    }
  }

  /**
   * Returns the OG menu name.
   *
   * @return string
   *   The OG menu name.
   */
  protected function getOgMenuName(): string {
    $og_menu_instance = $this->getOgMenuInstance();
    return 'ogmenu-' . $og_menu_instance->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

}
