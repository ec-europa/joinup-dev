<?php

declare(strict_types = 1);

namespace Drupal\rdf_entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a Rdf entity.
 *
 * We have this interface so we can join the other interfaces it extends.
 *
 * @ingroup rdf_entity
 */
interface RdfInterface extends ContentEntityInterface, EntityPublishedInterface, EntityOwnerInterface, EntityChangedInterface {

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
   * @param string $name
   *   The RDF entity's name.
   *
   * @return $this
   */
  public function setName(string $name): self;

  /**
   * Removes an entity from the passed graph.
   *
   * This method does not delete the entity entirely so it skips the delete
   * hooks.
   *
   * @param string $graph_id
   *   The ID of the graph.
   */
  public function deleteFromGraph(string $graph_id): void;

  /**
   * Checks if the entity has a specific graph.
   *
   * Returns false, if the entity is new.
   *
   * @param string $graph
   *   The graph to be checked ('draft', etc).
   *
   * @return bool
   *   TRUE if this entity has the specified graph.
   */
  public function hasGraph($graph);

  /**
   * Gets the entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the entity.
   */
  public function getCreatedTime();

  /**
   * Sets the entity creation timestamp.
   *
   * @param int $timestamp
   *   The entity creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

}
