<?php

declare(strict_types = 1);

namespace Drupal\joinup_menu;

/**
 * Interface for services that provide helper methods for dealing with menus.
 */
interface JoinupMenuHelperInterface {

  /**
   * Loads the entities related to the passed menu items.
   *
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent[] $menu_items
   *   The array of menu items.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of entities in the same order as their menu items.
   */
  public function loadEntitiesFromMenuItems(array $menu_items): array;

}
