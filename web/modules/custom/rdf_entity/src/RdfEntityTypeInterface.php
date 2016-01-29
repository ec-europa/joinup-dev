<?php
/**
 * @file
 * Contains \Drupal\rdf_entity\RdfInterface.
 */

namespace Drupal\rdf_entity;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a Rdf entity.
 *
 * We have this interface so we can join the other interfaces it extends.
 *
 * @ingroup rdf_entity
 */
interface RdfEntityTypeInterface extends ConfigEntityInterface {

}
