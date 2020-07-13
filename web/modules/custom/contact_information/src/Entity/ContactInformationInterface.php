<?php

declare(strict_types = 1);

namespace Drupal\contact_information\Entity;

use Drupal\joinup_workflow\EntityWorkflowStateInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * Interface for contact information entities in Joinup.
 */
interface ContactInformationInterface extends RdfInterface, EntityWorkflowStateInterface {

}
