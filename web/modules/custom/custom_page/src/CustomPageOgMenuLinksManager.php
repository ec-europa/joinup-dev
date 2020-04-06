<?php

declare(strict_types = 1);

namespace Drupal\custom_page;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\og_menu\OgMenuInstanceInterface;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Manages the OG Menu links of custom pages.
 */
class CustomPageOgMenuLinksManager implements CustomPageOgMenuLinksManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The menu link manager service.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * Builds a new custom page OG menu links updater service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MenuLinkManagerInterface $menu_link_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->menuLinkManager = $menu_link_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getChildren(NodeInterface $custom_page): array {
    $menu_link_content_storage = $this->entityTypeManager->getStorage('menu_link_content');
    $node_storage = $this->entityTypeManager->getStorage('node');

    $this->verifyCustomPage($custom_page);
    $children = [];
    if ($og_menu_instance = $this->getOgMenuInstanceByCustomPage($custom_page)) {
      $menu_name = "ogmenu-{$og_menu_instance->id()}";
      // Collect the IDs of links to the custom page.
      $mids = $menu_link_content_storage->getQuery()
        ->condition('bundle', 'menu_link_content')
        ->condition('menu_name', $menu_name)
        ->condition('link.uri', "entity:node/{$custom_page->id()}")
        ->execute();
      if ($mids) {
        $parents = [];
        /** @var \Drupal\menu_link_content\MenuLinkContentInterface $menu_link */
        foreach ($menu_link_content_storage->loadMultiple($mids) as $menu_link) {
          $parents[] = $menu_link->getPluginId();
        }
        if ($parents) {
          $children_ids = $menu_link_content_storage->getQuery()
            ->condition('bundle', 'menu_link_content')
            ->condition('menu_name', $menu_name)
            ->condition('parent', $parents, 'IN')
            ->execute();
          if ($children_ids) {
            foreach ($menu_link_content_storage->loadMultiple($children_ids) as $menu_link) {
              if ($uri = $menu_link->link->uri) {
                try {
                  $url = Url::fromUri($uri);
                  if ($url->isRouted() && $url->getRouteName() === 'entity.node.canonical' && ($parameters = $url->getRouteParameters()) && !empty($parameters['node'])) {
                    if ($node = $node_storage->load($parameters['node'])) {
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
  public function addLink(NodeInterface $custom_page): CustomPageOgMenuLinksManagerInterface {
    $menu_link_content_storage = $this->entityTypeManager->getStorage('menu_link_content');
    $this->verifyCustomPage($custom_page);
    if ($og_menu_instance = $this->getOgMenuInstanceByCustomPage($custom_page)) {
      $menu_link_content_storage->create([
        'title' => $custom_page->label(),
        'menu_name' => 'ogmenu-' . $og_menu_instance->id(),
        'link' => ['uri' => 'entity:node/' . $custom_page->id()],
        'enabled' => TRUE,
      ])->save();
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function moveLinks(NodeInterface $custom_page, $group_id): CustomPageOgMenuLinksManagerInterface {
    $menu_link_content_storage = $this->entityTypeManager->getStorage('menu_link_content');
    $this->verifyCustomPage($custom_page);
    if ($source_og_menu_instance = $this->getOgMenuInstanceByCustomPage($custom_page)) {
      if ($target_og_menu_instance = $this->getOgMenuInstanceByGroupId($group_id)) {
        $source_menu_name = "ogmenu-{$source_og_menu_instance->id()}";
        // Collect the IDs of links to the custom page.
        $mids = $menu_link_content_storage->getQuery()
          ->condition('bundle', 'menu_link_content')
          ->condition('menu_name', $source_menu_name)
          ->condition('link.uri', "entity:node/{$custom_page->id()}")
          ->execute();
        if ($mids) {
          $target_menu_name = "ogmenu-{$target_og_menu_instance->id()}";
          /** @var \Drupal\menu_link_content\MenuLinkContentInterface $menu_link */
          foreach ($menu_link_content_storage->loadMultiple($mids) as $menu_link) {
            // Change the OG menu instance of each link.
            $menu_link->set('menu_name', $target_menu_name)->save();
          };
        }
      }
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteLinks(NodeInterface $custom_page): CustomPageOgMenuLinksManagerInterface {
    $menu_link_content_storage = $this->entityTypeManager->getStorage('menu_link_content');
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
              $mids = $menu_link_content_storage->getQuery()
                ->condition('parent', "menu_link_content:{$instance->getDerivativeId()}")
                ->execute();
              if ($mids) {
                // Remove the relationship to the deleted parent menu link.
                /** @var \Drupal\menu_link_content\MenuLinkContentInterface $menu_link_content */
                foreach ($menu_link_content_storage->loadMultiple($mids) as $menu_link_content) {
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
  public function getOgMenuInstanceByCustomPage(NodeInterface $custom_page): ?OgMenuInstanceInterface {
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
  protected function verifyCustomPage(NodeInterface $custom_page): void {
    $bundle = $custom_page->bundle();
    if ($bundle !== 'custom_page') {
      throw new \InvalidArgumentException("The node is not a custom page, but a '$bundle'.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOgMenuInstanceByGroupId(string $group_id): ?OgMenuInstanceInterface {
    if (Rdf::load($group_id)) {
      $properties = [
        'type' => 'navigation',
        OgGroupAudienceHelperInterface::DEFAULT_FIELD => $group_id,
      ];
      $storage = $this->entityTypeManager->getStorage('ogmenu_instance');
      if ($instances = $storage->loadByProperties($properties)) {
        return reset($instances);
      }
    }
    return NULL;
  }

}
