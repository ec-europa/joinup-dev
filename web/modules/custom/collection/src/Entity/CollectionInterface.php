<?php

declare(strict_types = 1);

namespace Drupal\collection\Entity;

use Drupal\joinup_bundle_class\ShortIdInterface;
use Drupal\joinup_workflow\EntityWorkflowStateInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * Interface for collection entities in Joinup.
 */
interface CollectionInterface extends RdfInterface, ShortIdInterface, EntityWorkflowStateInterface {

  /**
   * Returns the IDs of the solutions that are affiliated with this collection.
   *
   * @return string[]
   *   The solutions.
   */
  public function getSolutionIds(): array;

}
