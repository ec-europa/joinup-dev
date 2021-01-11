<?php

declare(strict_types = 1);

namespace Drupal\contact_information;

use Drupal\rdf_entity\RdfInterface;

/**
 * Interface for services that inform about contact information relations.
 */
interface ContactInformationRelationInfoInterface {

  /**
   * Returns the groups that relate to a contact information entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $entity
   *   The contact information entity.
   *
   * @return \Drupal\joinup_group\Entity\GroupInterface[]
   *   A list of groups that reference the given contact information entity.
   */
  public function getContactInformationRelatedGroups(RdfInterface $entity): array;

}
