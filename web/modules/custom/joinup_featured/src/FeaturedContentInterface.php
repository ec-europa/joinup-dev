<?php

declare(strict_types = 1);

namespace Drupal\joinup_featured;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for content entities that can be featured.
 *
 * In Joinup moderators can designate important content to be "featured site
 * wide". Featured content will show prominently at the top of lists, is
 * highlighted with an icon, and the user can use facet filters to only see
 * featured content.
 *
 * Only moderators can decide which content is features, but all users can see
 * it. It applies to collections, solutions and community content.
 */
interface FeaturedContentInterface extends ContentEntityInterface {

  /**
   * Checks if the entity is featured site wide.
   *
   * @return bool
   *   TRUE if the entity is featured site wide, FALSE otherwise.
   */
  public function isFeatured(): bool;

  /**
   * Marks the entity as featured site wide.
   *
   * @return self
   *   The featured entity, for chaining.
   */
  public function feature(): FeaturedContentInterface;

  /**
   * Removes the site wide featured flag from the entity.
   *
   * @return self
   *   The unfeatured entity, for chaining.
   */
  public function unfeature(): FeaturedContentInterface;

}
