<?php

declare(strict_types = 1);

namespace Drupal\joinup_front_page;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\menu_link_content\MenuLinkContentInterface;

/**
 * Interface for services that deal with the front page menu.
 */
interface FrontPageMenuHelperInterface {

  /**
   * Fetches the menu item content entity for the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to fetch the menu item content entity for.
   *
   * @return \Drupal\menu_link_content\MenuLinkContentInterface|null
   *   The menu link content entity.
   */
  public function getFrontPageMenuItem(EntityInterface $entity): ?MenuLinkContentInterface;

  /**
   * Adds an entity to the front page menu.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to add in the front page menu.
   */
  public function pinToFrontPage(FieldableEntityInterface $entity): void;

  /**
   * Removes an entity from the front page menu.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to remove from the front page menu.
   */
  public function unpinFromFrontPage(FieldableEntityInterface $entity): void;

}
