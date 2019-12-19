<?php

declare(strict_types = 1);


namespace Drupal\joinup;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\menu_link_content\Entity\MenuLinkContent as MenuLinkContentEntity;

/**
 * Interface FrontPageMenuHelperInterface.
 *
 * @package Drupal\joinup
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

}
