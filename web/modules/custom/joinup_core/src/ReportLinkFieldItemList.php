<?php

namespace Drupal\joinup_core;

use Drupal\Core\Field\FieldItemList;

/**
 * Item list for the link to report inappropriate content.
 *
 * @see \Drupal\joinup_core\Plugin\Field\FieldType\ReportLinkItem
 */
class ReportLinkFieldItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    $this->ensureLoaded();
    return new \ArrayIterator($this->list);
  }

  /**
   * {@inheritdoc}
   */
  public function getValue($include_computed = FALSE) {
    $this->ensureLoaded();
    return parent::getValue($include_computed);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $this->ensureLoaded();
    return parent::isEmpty();
  }

  /**
   * Makes sure that the item list is never empty.
   *
   * For 'normal' fields that use database storage the field item list is
   * initially empty, but since this is a computed field this always has a
   * value.
   * Make sure the item list is always populated, so this field is not skipped
   * for rendering in EntityViewDisplay and friends.
   *
   * This trick has been borrowed from issue #2846554 which does the same for
   * the PathItem field.
   *
   * @see https://www.drupal.org/node/2846554
   */
  protected function ensureLoaded() {
    if (!isset($this->list[0])) {
      $this->list[0] = $this->createItem(0);
    }
  }

}
