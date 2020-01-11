<?php

declare(strict_types = 1);

namespace Drupal\joinup_front_page;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\menu_link_content\Entity\MenuLinkContent as MenuLinkContentEntity;

/**
 * Interface FrontPageMenuHelperInterface.
 *
 * @package Drupal\joinup_front_page
 */
interface FrontPageMenuHelperInterface {

  /**
   * Fetches the menu item content entity for the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to fetch the menu item content entity for.
   *
   * @return \Drupal\menu_link_content\Entity\MenuLinkContent|null
   *   The menu link content interface.
   */
  public function getFrontPageMenuItem(EntityInterface $entity): ?MenuLinkContentEntity;

  /**
   * Adds an entity to the front page menu.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to add in the front page menu.
   */
  public function pinSiteWide(FieldableEntityInterface $entity): void;

  /**
   * Removes an entity from the front page menu.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to remove from the front page menu.
   */
  public function unpinSiteWide(FieldableEntityInterface $entity): void;

  /**
   * Updates the search api index entry of the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be updated.
   */
  public function updateSearchApiEntry(EntityInterface $entity): void;

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
