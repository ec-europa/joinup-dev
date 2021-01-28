<?php

declare(strict_types = 1);

namespace Drupal\joinup_publication_date\Entity;

/**
 * Blanket implementation for EntityPublicationDateInterface.
 *
 * This will return the value set by the Publication Date module if available.
 *
 * @see \Drupal\joinup_publication_date\Entity\EntityPublicationDateInterface
 */
trait EntityPublicationDateTrait {

  /**
   * {@inheritdoc}
   */
  public function getPublicationDate(): ?int {
    // Check if we have a publication date set by the Publication Date module.
    if (!$this->hasField('published_at') || $this->getFieldDefinition('published_at')->getProvider() !== 'publication_date') {
      return NULL;
    }

    // Return NULL if no value is set, or if the default publication date is set
    // which is some bogus future value.
    // @see https://www.drupal.org/project/publication_date/issues/3066446
    if ($this->get('published_at')->isEmpty() || empty($this->get('published_at')->value) || $this->get('published_at')->value == PUBLICATION_DATE_DEFAULT) {
      return NULL;
    }

    return (int) $this->get('published_at')->value;
  }

}
