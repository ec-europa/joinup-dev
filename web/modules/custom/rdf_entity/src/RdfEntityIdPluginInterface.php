<?php

namespace Drupal\rdf_entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for entity ID generator plugins.
 */
interface RdfEntityIdPluginInterface {

  /**
   * Generates the entity ID.
   *
   * @return string
   *   An entity ID.
   */
  public function generate();

  /**
   * Sets the entity for which the ID is being generated.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return $this
   */
  public function setEntity(ContentEntityInterface $entity);

  /**
   * Gets the entity for which the ID is being generated.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The entity.
   */
  public function getEntity();

}
