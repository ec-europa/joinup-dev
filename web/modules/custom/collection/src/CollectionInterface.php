<?php

/**
 * @file
 * Contains \Drupal\collection\CollectionInterface.
 */

namespace Drupal\collection;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Collection entities.
 *
 * @ingroup collection
 */
interface CollectionInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Collection name.
   *
   * @return string
   *   Name of the Collection.
   */
  public function getName();

  /**
   * Sets the Collection name.
   *
   * @param string $name
   *   The Collection name.
   *
   * @return \Drupal\collection\CollectionInterface
   *   The called Collection entity.
   */
  public function setName($name);

  /**
   * Gets the Collection creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Collection.
   */
  public function getCreatedTime();

  /**
   * Sets the Collection creation timestamp.
   *
   * @param int $timestamp
   *   The Collection creation timestamp.
   *
   * @return \Drupal\collection\CollectionInterface
   *   The called Collection entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Collection published status indicator.
   *
   * Unpublished Collection are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Collection is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Collection.
   *
   * @param bool $published
   *   TRUE to set this Collection to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\collection\CollectionInterface
   *   The called Collection entity.
   */
  public function setPublished($published);

}
