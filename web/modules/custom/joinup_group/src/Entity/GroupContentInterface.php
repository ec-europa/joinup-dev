<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\rdf_entity\RdfInterface;

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
   * @return \Drupal\rdf_entity\RdfInterface
   *   The parent group.
   *
   * @throws \Drupal\joinup_group\Exception\MissingGroupException
   *   The the parent group is missing.
   */
  public function getGroup(): RdfInterface;

}
