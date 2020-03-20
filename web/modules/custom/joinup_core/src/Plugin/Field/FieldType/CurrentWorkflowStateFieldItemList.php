<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Field item list for the current workflow state computed field.
 */
class CurrentWorkflowStateFieldItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    // We don't need to compute an initial value since the values are set by the
    // widget. Just set an empty value.
    // @see \Drupal\joinup_workflow\Plugin\Field\FieldWidget\CurrentWorkflowStateWidget::validateFormElement()
    $this->list[0] = $this->createItem(0, '');
  }

}
