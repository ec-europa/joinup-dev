<?php

declare(strict_types = 1);

namespace Drupal\joinup_front_page\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for entities that can be pinned to the frontpage.
 *
 * Moderators can pin communities, solutions and community content to the Joinup
 * front page.
 *
 * *Note that this interface is NOT for pinning entities in groups*
 *
 * There is a similarly named concept in Joinup which allows content to be
 * "Pinned inside a collection/solution" but this is implemented using a meta
 * entity. The same naming is used since from a functional perspective the
 * actions of "pinning" content to a collection / solution / front page is
 * equivalent.
 *
 * See PinnableGroupContentInterface for pinning group content.
 *
 * @see \Drupal\joinup_group\Entity\PinnableGroupContentInterface
 */
interface PinnableToFrontpageInterface extends ContentEntityInterface {

  /**
   * Returns whether or not the entity is pinned to the front page.
   *
   * @return bool
   *   Whether or not the entity is pinned to the front page.
   */
  public function isPinnedToFrontPage(): bool;

  /**
   * Adds the entity to the front page menu.
   *
   * @return \Drupal\joinup_front_page\Entity\PinnableToFrontpageInterface
   *   The entity, for chaining.
   */
  public function pinToFrontPage(): PinnableToFrontpageInterface;

  /**
   * Removes the entity from the front page menu.
   *
   * @return \Drupal\joinup_front_page\Entity\PinnableToFrontpageInterface
   *   The entity, for chaining.
   */
  public function unpinFromFrontPage(): PinnableToFrontpageInterface;

}
