<?php

declare(strict_types = 1);

namespace Drupal\joinup_publication_date\Entity;

use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Interface for entities that have a publication timestamp.
 */
interface EntityPublicationTimeInterface extends EntityPublishedInterface {

  /**
   * Returns the timestamp when the entity was first published.
   *
   * @return int|null
   *   Publication timestamp of the entity, or NULL if the entity has never been
   *   published.
   */
  public function getPublicationTime(): ?int;

}
