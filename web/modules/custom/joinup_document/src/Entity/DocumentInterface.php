<?php

declare(strict_types = 1);

namespace Drupal\joinup_document\Entity;

use Drupal\joinup_community_content\Entity\CommunityContentInterface;

/**
 * Interface for document entities in Joinup.
 */
interface DocumentInterface extends CommunityContentInterface {

  /**
   * Returns the publication date of the document.
   *
   * This method will return the value of the field_document_publication_date
   * field, if set, otherwise, the value from the published_at property. If none
   * of them are set, it will return NULL.
   *
   * @return int|null
   *   The publication date of the document, or NULL if none is set.
   */
  public function getPublicationDate(): ?int;

}
