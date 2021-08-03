<?php

declare(strict_types = 1);

namespace Drupal\contact_information\Entity;

use Drupal\joinup_group\Entity\GroupInterface;
use Drupal\joinup_workflow\EntityWorkflowStateInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * Interface for contact information entities in Joinup.
 *
 * A contact information entity provides a name and contact details and is used
 * by communities, solutions and asset releases. The data is linked using a
 * standard entity reference field on the parent entity, and even though it can
 * be related to groups this is not considered group content.
 *
 * In Joinup a contact information entity is not shared between groups, so every
 * contact info entity belongs only to 1 single parent group. However it can
 * belong to many asset releases within a solution.
 */
interface ContactInformationInterface extends RdfInterface, EntityWorkflowStateInterface {

  /**
   * Returns the group that includes this entity among their contacts.
   *
   * @return \Drupal\joinup_group\Entity\GroupInterface|null
   *   The group that has this contact information entity listed, or NULL if no
   *   group references it.
   */
  public function getRelatedGroup(): ?GroupInterface;

}
