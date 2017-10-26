<?php

namespace Drupal\custom_page;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\og\OgGroupAudienceHelperInterface;

/**
 * Manages the OG Menu links of custom pages.
 */
class CustomPageOgMenuLinksManager implements CustomPageOgMenuLinksManagerInterface {

  /**
   * The OG menu instance storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $ogMenuInstanceStorage;

  /**
   * The menu link manager service.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * The menu link content storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   */
  protected $menuLinkContentStorage;

  /**
   * Builds a new custom page OG menu links updater service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MenuLinkManagerInterface $menu_link_manager) {
    $this->ogMenuInstanceStorage = $entity_type_manager->getStorage('ogmenu_instance');
    $this->menuLinkContentStorage = $entity_type_manager->getStorage('menu_link_content');
    $this->menuLinkManager = $menu_link_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getChildren(NodeInterface $custom_page) {
    $this->verifyCustomPage($custom_page);
    $children = [];
    if ($og_menu_instance = $this->getOgMenuInstanceByCustomPage($custom_page)) {
      $menu_name = "ogmenu-{$og_menu_instance->id()}";
      // Collect the IDs of link to the custom page.
      $mids = $this->menuLinkContentStorage->getQuery()
        ->condition('bundle', 'menu_link_content')
        ->condition('menu_name', $menu_name)
        ->condition('link.uri', "internal:/{$custom_page->toUrl()->getInternalPath()}")
        ->execute();
      if ($mids) {
        $parents = [];
        /** @var \Drupal\menu_link_content\MenuLinkContentInterface $menu_link */
        foreach ($this->menuLinkContentStorage->loadMultiple($mids) as $menu_link) {
          $parents[] = $menu_link->getPluginId();
        }
        if ($parents) {
          $children_ids = $this->menuLinkContentStorage->getQuery()
            ->condition('bundle', 'menu_link_content')
            ->condition('menu_name', $menu_name)
            ->condition('parent', $parents, 'IN')
            ->execute();
          if ($children_ids) {
            foreach ($this->menuLinkContentStorage->loadMultiple($children_ids) as $menu_link) {
              if ($uri = $menu_link->link->uri) {
                try {
                  $url = Url::fromUri($uri);
                  if ($url->isRouted() && $url->getRouteName() === 'entity.node.canonical' && ($parameters = $url->getRouteParameters()) && !empty($parameters['node'])) {
                    if ($node = Node::load($parameters['node'])) {
                      $children[$parameters['node']] = $node;
                    }
                  }
                }
                catch (\Exception $exception) {
                  // Fail silently.
                }
              }
            }
          }
        }
      }
    }
    return $children;
  }

  /**
   * {@inheritdoc}
   */
  public function addLink(NodeInterface $custom_page) {
    $this->verifyCustomPage($custom_page);
    if ($og_menu_instance = $this->getOgMenuInstanceByCustomPage($custom_page)) {
      $this->menuLinkContentStorage->create([
        'title' => $custom_page->label(),
        'menu_name' => 'ogmenu-' . $og_menu_instance->id(),
        'link' => ['uri' => 'internal:/node/' . $custom_page->id()],
        // The 'exclude_from_menu' property is used as a hidden API trick to
        // allow a disabled menu item.
        'enabled' => empty($custom_page->exclude_from_menu),
      ])->save();
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function moveLinks(NodeInterface $custom_page, $group_id) {
    $this->verifyCustomPage($custom_page);
    if ($source_og_menu_instance = $this->getOgMenuInstanceByCustomPage($custom_page)) {
      if ($target_og_menu_instance = $this->getOgMenuInstanceByGroupId($group_id)) {
        $source_menu_name = "ogmenu-{$source_og_menu_instance->id()}";
        // Collect the IDs of link to the custom page.
        $mids = $this->menuLinkContentStorage->getQuery()
          ->condition('bundle', 'menu_link_content')
          ->condition('menu_name', $source_menu_name)
          ->condition('link.uri', "internal:/{$custom_page->toUrl()->getInternalPath()}")
          ->execute();
        if ($mids) {
          $target_menu_name = "ogmenu-{$target_og_menu_instance->id()}";
          /** @var \Drupal\menu_link_content\MenuLinkContentInterface $menu_link */
          foreach ($this->menuLinkContentStorage->loadMultiple($mids) as $menu_link) {
            // Change the OG menu instance of each link.
            $menu_link->set('menu_name', $target_menu_name)->save();
          };
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteLinks(NodeInterface $custom_page) {
    $this->verifyCustomPage($custom_page);
    /** @var \Drupal\Core\Menu\MenuLinkTreeInterface $t */
    if ($og_menu_instance = $this->getOgMenuInstanceByCustomPage($custom_page)) {
      $menu_name = "ogmenu-{$og_menu_instance->id()}";
      foreach ($custom_page->uriRelationships() as $rel) {
        $url = $custom_page->toUrl($rel);
        // Delete all MenuLinkContent links that point to this entity route.
        if ($result = $this->menuLinkManager->loadLinksByRoute($url->getRouteName(), $url->getRouteParameters())) {
          /** @var \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent $instance */
          foreach ($result as $id => $instance) {
            if ($instance->getMenuName() === $menu_name && $instance->isDeletable() && strpos($id, 'menu_link_content:') === 0) {
              $instance->deleteLink();
              // Search for children of deleted menu link.
              $mids = $this->menuLinkContentStorage->getQuery()
                ->condition('parent', "menu_link_content:{$instance->getDerivativeId()}")
                ->execute();
              if ($mids) {
                // Remove the relationship to the deleted parent menu link.
                /** @var \Drupal\menu_link_content\MenuLinkContentInterface $menu_link_content */
                foreach ($this->menuLinkContentStorage->loadMultiple($mids) as $menu_link_content) {
                  $menu_link_content->set('parent', NULL)->save();
                }
              }
            }
          }
        }
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOgMenuInstanceByCustomPage(NodeInterface $custom_page) {
    $this->verifyCustomPage($custom_page);
    if ($group_id = $custom_page->get(OgGroupAudienceHelperInterface::DEFAULT_FIELD)->target_id) {
      return $this->getOgMenuInstanceByGroupId($group_id);
    }
    return NULL;
  }

  /**
   * Checks that the passed in node is a custom page.
   *
   * @param \Drupal\node\NodeInterface $custom_page
   *   The node to check.
   */
  protected function verifyCustomPage(NodeInterface $custom_page) : void {
    $bundle = $custom_page->bundle();
    if ($bundle() !== 'custom_page') {
      throw new \InvalidArgumentException("The entity is not a custom page, but a '$bundle'.");
    }
  }

  /**
   * Gets the OG menu instance, given a group ID.
   *
   * @param string $group_id
   *   The ID of the group where is attached the OG menu instance.
   *
   * @return \Drupal\og_menu\OgMenuInstanceInterface|null
   *   The OG menu instance or NULL if none can be determined.
   */
  protected function getOgMenuInstanceByGroupId($group_id) {
    $properties = [
      'type' => 'navigation',
      OgGroupAudienceHelperInterface::DEFAULT_FIELD => $group_id,
    ];
    if ($instances = $this->ogMenuInstanceStorage->loadByProperties($properties)) {
      return reset($instances);
    }
    return NULL;
  }

}
