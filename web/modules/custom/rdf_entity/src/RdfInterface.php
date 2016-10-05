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

}
