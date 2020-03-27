<?php

declare(strict_types = 1);

namespace Drupal\custom_page;

use Drupal\Core\Entity\EntityTypeManagerInterface;

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
   * The custom page menu links manager.
   *
   * @var \Drupal\custom_page\CustomPageOgMenuLinksManagerInterface
   */
  protected $menuLinkManager;

  /**
   * Builds a new custom page OG menu links updater service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\custom_page\CustomPageOgMenuLinksManagerInterface $menuLinkManager
   *   The custom page menu link manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, CustomPageOgMenuLinksManagerInterface $menuLinkManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->menuLinkManager = $menuLinkManager;
  }

  /**
   * Returns the custom pages that belong to the given group.
   *
   * @param string $group_id
   *   The entity ID of the group for which to return the custom pages.
   * @param bool $include_disabled
   *   Whether or not to include custom pages that are disabled by the group
   *   facilitators and are not visible in the group menu. Defaults to FALSE.
   *
   * @return \Drupal\node\NodeInterface[]
   *   The custom page entities.
   */
  public function getCustomPagesByGroupId(string $group_id, bool $include_disabled = FALSE): array {
    $menu_instance = $this->menuLinkManager->getOgMenuInstanceByGroupId($group_id);
    if (!empty($menu_instance)) {
      $properties = [
        'bundle' => 'menu_link_content',
        'menu_name' => "ogmenu-{$menu_instance->id()}",
      ];
      if (!$include_disabled) {
        $properties['enabled'] = 1;
      }

      $storage = $this->entityTypeManager->getStorage('menu_link_content');
      $entities = $storage->loadByProperties($properties);
    }
    return [];
  }

}
