<?php

declare(strict_types = 1);

namespace Drupal\owner\Entity;

use Drupal\joinup_workflow\EntityWorkflowStateInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * Interface for owner entities in Joinup.
 */
interface OwnerInterface extends RdfInterface, EntityWorkflowStateInterface {

}
