<?php

namespace Drupal\rdf_entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a Rdf entity.
 *
 * We have this interface so we can join the other interfaces it extends.
 *
 * @ingroup rdf_entity
 */
interface RdfInterface extends ContentEntityInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Gets the name of the rdf entity.
   *
   * @return string
   *   The name of the rdf entity.
   */
  public function getName();

  /**
   * Sets the name of the rdf entity.
   *
   * @param int $name
   *   The rdf entity's name.
   *
   * @return $this
   */
  public function setName($name);

  /**
   * Removes an entity from the passed graph.
   *
   * This method does not delete the entity entirely so it skips the delete
   * hooks.
   *
   * @param string $graph
   *   The graph machine name.
   */
  public function deleteFromGraph($graph);

  /**
   * Checks if the entity has a specific graph.
   *
   * @param string $graph
   *   The graph to be checked ('draft', etc).
   *
   * @return bool
   *   TRUE if this entity has the specified graph.
   */
  public function hasGraph($graph);

}
