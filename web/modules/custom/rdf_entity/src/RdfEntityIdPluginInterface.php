<?php

namespace Drupal\rdf_entity;

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
   * Gets the entity for which the ID is being generated.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The entity.
   */
  public function getEntity();

}
