<?php

namespace Drupal\joinup_core\Traits;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Trait for fields that need to support multiple cardinality for existing data.
 */
trait LegacyMultipleCardinalitySupportingFieldWidgetTrait {

  /**
   * Whether or not the current field instance already has multiple values.
   *
   * @var bool
   */
  protected $hasMultipleValues = FALSE;

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    $this->hasMultipleValues = $items->count() > 1;

    // Add a new empty item if it doesn't exist yet at this delta.
    if (!isset($items[0])) {
      $items->appendItem();
    }

    return parent::form($items, $form, $form_state, $get_delta);
  }

  /**
   * {@inheritdoc}
   */
  protected function handlesMultipleValues() {
    // If there already are multiple values present in the field, then these
    // have been imported from the legacy Drupal 6 site. Allow the field to be
    // edited as a multivalue field.
    return !$this->hasMultipleValues;
  }

}
