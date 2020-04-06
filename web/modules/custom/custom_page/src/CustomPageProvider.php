<?php

declare(strict_types = 1);

namespace Drupal\custom_page;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;
use Drupal\og\OgGroupAudienceHelperInterface;

/**
 * Provides custom pages to whoever desires them.
 */
class CustomPageProvider implements CustomPageProviderInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The core menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * The custom page menu links manager.
   *
   * @var \Drupal\custom_page\CustomPageOgMenuLinksManagerInterface
   */
  protected $customPageOgMenuLinksManager;

  /**
   * Builds a new custom page OG menu links updater service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menuLinkManager
   *   The core menu link manager.
   * @param \Drupal\custom_page\CustomPageOgMenuLinksManagerInterface $customPageOgMenuLinksManager
   *   The custom page menu link manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, MenuLinkManagerInterface $menuLinkManager, CustomPageOgMenuLinksManagerInterface $customPageOgMenuLinksManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->menuLinkManager = $menuLinkManager;
    $this->customPageOgMenuLinksManager = $customPageOgMenuLinksManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomPagesByGroupId(string $group_id, bool $exclude_disabled = TRUE): array {
    // Get a list of all custom pages belonging to the group.
    $custom_page_ids = $this->getCustomPageIds($group_id);

    if (!empty($custom_page_ids) && $exclude_disabled) {
      // Filter out all pages that are disabled in the group's menu instance.
      $ogmenu_instance = $this->customPageOgMenuLinksManager->getOgMenuInstanceByGroupId($group_id);
      if (empty($ogmenu_instance)) {
        return [];
      }

      $custom_page_ids = array_filter($custom_page_ids, function (string $custom_page_id) use ($ogmenu_instance) {
        $menu_links = $this->menuLinkManager->loadLinksByRoute(
          'entity.node.canonical',
          ['node' => $custom_page_id],
          'ogmenu-' . $ogmenu_instance->id()
        );
        /** @var \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent $menu_link */
        $menu_link = reset($menu_links);

        // Sanity check that our data is valid.
        if (empty($menu_link) || !$menu_link instanceof MenuLinkContent || empty($entity_id = $menu_link->getPluginDefinition()['metadata']['entity_id'])) {
          return FALSE;
        }

        $menu_link_entity = $this->entityTypeManager->getStorage('menu_link_content')->load($entity_id);
        return !empty($menu_link_entity) && $menu_link_entity instanceof MenuLinkContentInterface && $menu_link_entity->isEnabled();
      });
    }

    if (empty($custom_page_ids)) {
      return [];
    }

    return $this->entityTypeManager->getStorage('node')->loadMultiple($custom_page_ids);
  }

  /**
   * Returns the custom page IDs of published custom pages in the given group.
   *
   * @param string $group_id
   *   The ID of the group for which to return the published custom page IDs.
   *
   * @return string[]
   *   An array of group IDs.
   */
  protected function getCustomPageIds(string $group_id): array {
    return $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->condition(OgGroupAudienceHelperInterface::DEFAULT_FIELD . '.target_id', $group_id)
      ->condition('type', 'custom_page')
      ->condition('status', 1)
      ->execute();
  }

}
