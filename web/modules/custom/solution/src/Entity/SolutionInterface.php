<?php

declare(strict_types = 1);

namespace Drupal\solution\Entity;

use Drupal\collection\Entity\CollectionContentInterface;
use Drupal\joinup_group\Entity\GroupContentInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * Interface for solution entities in Joinup.
 */
interface SolutionInterface extends RdfInterface, CollectionContentInterface, GroupContentInterface {

}
