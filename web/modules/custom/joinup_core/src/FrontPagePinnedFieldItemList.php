<?php

declare(strict_types = 1);

namespace Drupal\joinup_core;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

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
      /** @var \Drupal\joinup_front_page\FrontPageMenuHelperInterface $front_page_helper */
      $front_page_helper = \Drupal::service('joinup_front_page.front_page_helper');
      $value = empty($front_page_helper->getFrontPageMenuItem($this->getEntity())) ? 0 : 1;
      $this->list[0] = $this->createItem(0, $value);
    }
  }

}
