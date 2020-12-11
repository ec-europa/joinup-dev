<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for entities that are group content.
 *
 * The following group types can be returned for these bundles:
 * - collections: community content, custom pages, and solutions.
 * - solutions: asset releases and asset distributions.
 */
interface GroupContentInterface extends ContentEntityInterface {

  /**
   * Returns the group to which this entity belongs.
   *
   * @return \Drupal\joinup_group\Entity\GroupInterface
   *   The parent group.
   *
   * @throws \Drupal\joinup_group\Exception\MissingGroupException
   *   Thrown when the parent group is missing.
   */
  public function getGroup(): GroupInterface;

  /**
   * Returns the ID of the group to which this entity belongs.
   *
   * @return string
   *   The parent group ID.
   *
   * @throws \Drupal\joinup_group\Exception\MissingGroupException
   *   Thrown when the parent group is missing.
   */
  public function getGroupId(): string;

}
