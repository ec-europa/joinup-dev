<?php

declare(strict_types = 1);

namespace Drupal\custom_page;

/**
 * Provides custom pages to whoever desires them.
 */
class CustomPageProvider implements CustomPageProviderInterface {

  /**
   * The custom page menu links manager.
   *
   * @var \Drupal\custom_page\CustomPageOgMenuLinksManagerInterface
   */
  protected $menuLinkManager;

  /**
   * Builds a new custom page OG menu links updater service.
   *
   * @param \Drupal\custom_page\CustomPageOgMenuLinksManagerInterface $menuLinkManager
   *   The custom page menu link manager.
   */
  public function __construct(CustomPageOgMenuLinksManagerInterface $menuLinkManager) {
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
    // @todo Implement.
    return [];
  }

}
