<?php

namespace Drupal\custom_page;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\node\NodeInterface;
use Drupal\og\OgGroupAudienceHelperInterface;

/**
 * Updates the OG Menu links of custom pages.
 */
class CustomPageOgMenuLinksUpdater implements CustomPageOgMenuLinksUpdaterInterface {

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
  public function addLink(NodeInterface $custom_page) {
    if ($og_menu_instance = $this->getOgMenuInstance($custom_page)) {
      MenuLinkContent::create([
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
  public function deleteLinks(NodeInterface $custom_page) {
    /** @var \Drupal\Core\Menu\MenuLinkTreeInterface $t */
    if ($og_menu_instance = $this->getOgMenuInstance($custom_page)) {
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
                foreach (MenuLinkContent::loadMultiple($mids) as $menu_link_content) {
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
   * Gets the OG menu instance, given a custom page.
   *
   * @param \Drupal\node\NodeInterface $custom_page
   *   The custom page entity.
   *
   * @return \Drupal\og_menu\OgMenuInstanceInterface|null
   *   The OG menu instance or NULL if none can be determined.
   */
  protected function getOgMenuInstance(NodeInterface $custom_page) {
    $field_name = OgGroupAudienceHelperInterface::DEFAULT_FIELD;
    // If the node have no OG audience, exit.
    if (!$group_id = $custom_page->{$field_name}->target_id) {
      return NULL;
    }

    // Fetch the menu.
    $properties = ['type' => 'navigation', $field_name => $group_id];
    if (!$instances = $this->ogMenuInstanceStorage->loadByProperties($properties)) {
      return NULL;
    }
    return reset($instances);
  }

}
