<?php

declare(strict_types = 1);

namespace Drupal\joinup_group;

use Drupal\rdf_entity\RdfInterface;

/**
 * An interface for services that provide information about group relations.
 */
interface JoinupGroupRelationInfoInterface {

  /**
   * Returns the groups that relate to a contact information entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $entity
   *   The contact information entity.
   *
   * @return \Drupal\rdf_entity\RdfInterface[]
   *   A list of rdf entities that reference the given contact information
   *   entity.
   */
  public function getContactInformationRelatedGroups(RdfInterface $entity): array;

}
