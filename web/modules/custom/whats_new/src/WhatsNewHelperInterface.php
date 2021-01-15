<?php

declare(strict_types = 1);

namespace Drupal\whats_new;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface for the WhatsNewHelper service.
 */
interface WhatsNewHelperInterface {

  /**
   * Checks whether the given menu has at least one featured link.
   *
   * @param string $menu_name
   *   The menu name.
   *
   * @return bool
   *   Whether the menu has any featured link already.
   */
  public function menuHasFeaturedLinks(string $menu_name): bool;

  /**
   * Checks whether a menu item with enabled flagging exists for this entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   (optional) The entity to search links for.
   *
   * @return array
   *   An array of menu link content IDs.
   */
  public function getFlagEnabledMenuLinks(?EntityInterface $entity): array;

  /**
   * Returns whether the user has viewed the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity object.
   *
   * @return bool
   *   Whether the user has viewed the entity.
   */
  public function userHasViewedEntity(EntityInterface $entity): bool;

  /**
   * Adds a flag to the given entity for the current user.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the user has viewed.
   */
  public function setUserHasViewedEntity(EntityInterface $entity): void;

}
