<?php

declare(strict_types = 1);

namespace Drupal\joinup_document\Entity;

use Drupal\joinup_community_content\Entity\CommunityContentBase;

/**
 * Entity subclass for the 'document' bundle.
 */
class Document extends CommunityContentBase implements DocumentInterface {

  /**
   * {@inheritdoc}
   */
  public function getPublicationTime(): ?int {
    // This method will return the value of the field_document_publication_date
    // field, if set, otherwise, the value from the published_at property. If
    // none of them are set, it will return NULL.
    $publication_date_item_list = $this->get('field_document_publication_date');
    if (!$publication_date_item_list->isEmpty() && $value = $publication_date_item_list->first()->value) {
      return \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s', $value, new \DateTimeZone('UTC'))->getTimestamp();
    }

    if ($this->get('published_at')->isEmpty() || empty($this->get('published_at')->value) || $this->get('published_at')->value == PUBLICATION_DATE_DEFAULT) {
      return NULL;
    }

    return (int) $this->get('published_at')->value;
  }

}
