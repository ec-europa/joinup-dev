<?php

declare(strict_types = 1);

namespace Drupal\joinup_group;

use Drupal\Core\Entity\EntityInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * An interface for services that provide information about group relations.
 */
interface JoinupGroupRelationInfoInterface {

  /**
   * Retrieves the parent of the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group content entity.
   *
   * @return \Drupal\rdf_entity\RdfInterface|null
   *   The rdf entity the passed entity belongs to, or NULL when no group is
   *    found.
   */
  public function getParent(EntityInterface $entity): ?RdfInterface;

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
