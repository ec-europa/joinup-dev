<?php

namespace Drupal\rdf_entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Provides an interface defining a Rdf entity.
 *
 * We have this interface so we can join the other interfaces it extends.
 *
 * @ingroup rdf_entity
 */
interface RdfInterface extends ContentEntityInterface, EntityPublishedInterface {

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
   *    The graph machine name.
   */
  public function deleteFromGraph($graph);

  /**
   * Provides a setter for the 'uri' base field.
   *
   * @param string $uri
   *   The URI to be set.
   *
   * @return $this
   *
   * @throws \InvalidArgumentException
   *   If the URI is already set and different.
   */
  public function setUri($uri);

  /**
   * Gets the URI of th entity.
   *
   * @return string
   *   The URI of th entity.
   */
  public function getUri();

  /**
   * Gets a hash from the URI.
   *
   * @return string
   *   The hashed URI.
   *
   * @throws \RuntimeException
   *   If the URI is not yet set.
   */
  public function getUriHash();

}
