<?php

namespace Drupal\joinup_core\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\joinup_core\Traits\LegacyMultipleCardinalitySupportingFieldWidgetTrait;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;

/**
 * Widget for link fields that support multiple cardinality for existing data.
 *
 * In the Drupal 6 version of Joinup it was possible to enter multiple values
 * for some link fields, but in the new version we only allow to enter a single
 * value. However, it should still be possible to edit existing multivalue data
 * for content that was migrated from Drupal 6.
 *
 * @FieldWidget(
 *   id = "link_legacy_multicardinality",
 *   label = @Translation("With legacy multivalue support"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LegacyMultipleCardinalitySupportingLinkWidget extends LinkWidget {

  use LegacyMultipleCardinalitySupportingFieldWidgetTrait {
    form as traitForm;
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    $built_form = $this->traitForm($items, $form, $form_state, $get_delta);

    // Apply the title of the field directly to the URI part if we are showing a
    // single value field. Normally this is put in place by the #title part of
    // the 'field_multiple_value_form' template.
    if (!$this->hasMultipleValues) {
      $built_form['widget']['uri']['#title'] = $this->fieldDefinition->getLabel();
    }

    return $built_form;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // The link widget expects to be passed an array of values. If we tricked it
    // in becoming a single value widget, make sure it still gets what it wants.
    if (!$this->hasMultipleValues) {
      $values = [$values];
    }
    return parent::massageFormValues($values, $form, $form_state);
  }

}
