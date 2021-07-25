<?php

declare(strict_types = 1);

namespace Drupal\collection\Entity;

use Drupal\joinup_group\Entity\GroupContentInterface;

/**
 * Interface for entities that are collection content.
 *
 * This comprises community content, custom pages, and solutions.
 */
interface CommunitiesContentInterface extends GroupContentInterface {

  /**
   * Returns the collection to which this entity belongs.
   *
   * @return \Drupal\collection\Entity\CommunityInterface
   *   The collection. It could be the parent collection or a collection
   *   ancestor.
   *
   * @throws \Drupal\collection\Exception\MissingCommunityException
   *   Thrown when the collection has not been set on the entity.
   */
  public function getCommunity(): CommunityInterface;

}
