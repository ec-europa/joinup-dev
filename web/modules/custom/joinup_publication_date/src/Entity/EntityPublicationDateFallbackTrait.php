<?php

declare(strict_types = 1);

namespace Drupal\joinup_publication_date\Entity;

use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Implements EntityPublicationDateInterface.
 *
 * This serves as a fallback for entities that do not yet store the publication
 * date. If the entity is published it will return the creation date.
 *
 * If in the future the actual publication date will be stored this trait can be
 * trivially swapped out with the 'real' EntityPublicationDateTrait.
 *
 * @see \Drupal\joinup_publication_date\Entity\EntityPublicationDateInterface
 * @see \Drupal\joinup_publication_date\Entity\EntityPublicationDateTrait
 */
trait EntityPublicationDateFallbackTrait {

  /**
   * {@inheritdoc}
   */
  public function getPublicationTime(): ?int {
    // We don't know the actual initial publication date. If the entity is
    // currently unpublished, pretend it has never been published.
    if ($this instanceof EntityPublishedInterface && !$this->isPublished()) {
      return NULL;
    }

    // No unified interface exists yet to get the creation time from an entity.
    // But there is a method that is nearly universally supported.
    // @see https://www.drupal.org/project/drupal/issues/2833378
    if (method_exists($this, 'getCreatedTime')) {
      $created_time = $this->getCreatedTime();
      if ($created_time && is_numeric($created_time)) {
        return (int) $created_time;
      }
    }

    return NULL;
  }

}
