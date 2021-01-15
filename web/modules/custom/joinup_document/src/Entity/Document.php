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
  public function getPublicationDate(): ?int {
    $publication_date_item_list = $this->get('field_document_publication_date');
    if (!$publication_date_item_list->isEmpty() && $value = $publication_date_item_list->first()->value) {
      return strtotime($value);
    }

    if ($this->get('published_at')->isEmpty() || empty($this->get('published_at')->value) || $this->get('published_at')->value == PUBLICATION_DATE_DEFAULT) {
      return NULL;
    }

    return (int) $this->get('published_at')->value;
  }

}
