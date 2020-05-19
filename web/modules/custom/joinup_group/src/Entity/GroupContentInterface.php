<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Entity;

use Drupal\rdf_entity\RdfInterface;

/**
 * Interface for entities that are group content.
 *
 * This comprises community content, custom pages, and solutions.
 */
interface GroupContentInterface {

  /**
   * Returns the group to which this entity belongs.
   *
   * @return \Drupal\rdf_entity\RdfInterface|null
   *   The parent group.
   */
  public function getGroup(): ?RdfInterface;

}
