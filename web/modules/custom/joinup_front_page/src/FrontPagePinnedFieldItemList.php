<?php

declare(strict_types = 1);

namespace Drupal\joinup_front_page;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\joinup_front_page\Entity\PinnableToFrontpageInterface;

/**
 * Computed field that returns if the entity is pinned on the front page.
 */
class FrontPagePinnedFieldItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue(): void {
    if (!isset($this->list[0])) {
      $entity = $this->getEntity();
      $value = $entity instanceof PinnableToFrontpageInterface ? (int) $entity->isPinnedToFrontPage() : 0;
      $this->list[0] = $this->createItem(0, $value);
    }
  }

}
