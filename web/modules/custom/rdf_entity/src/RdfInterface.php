<?php
/**
 * @file
 * Contains \Drupal\rdf_entity\RdfInterface.
 */

namespace Drupal\rdf_entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Rdf entity.
 *
 * We have this interface so we can join the other interfaces it extends.
 *
 * @ingroup rdf_entity
 */
interface RdfInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
