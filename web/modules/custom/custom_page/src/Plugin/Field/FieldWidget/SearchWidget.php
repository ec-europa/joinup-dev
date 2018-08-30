<?php

namespace Drupal\custom_page\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api_field\Plugin\Field\FieldWidget\SearchWidget as DefaultSearchWidget;

/**
 * Plugin implementation of the 'search_api_field_custom_page' widget.
 *
 * Adds a checkbox to allow users to include content shared inside the
 * collection, improves labeling and hides unused fields.
 *
 * @FieldWidget(
 *   id = "search_api_field_custom_page",
 *   label = @Translation("Custom page search widget"),
 *   field_types = {
 *     "search_api_field"
 *   }
 * )
 */
class SearchWidget extends DefaultSearchWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Swap the default label with one that better represents our functionality.
    $element['enabled']['#title'] = $this->t('Display a community content listing');

    // There is no need to allow customizing the facets. For now.
    foreach (['fields', 'refresh_rows', 'refresh'] as $key) {
      $element['wrapper'][$key]['#access'] = FALSE;
    }

    /** @var \Drupal\search_api_field\Plugin\Field\FieldType\SearchItem $item */
    $item = $items[$delta];
    $default_values = $item->get('value')->getValue();

    $element['wrapper']['show_shared'] = [
      '#type' => 'checkbox',
      '#title' => t('Include content shared in the collection'),
      '#default_value' => $default_values['show_shared'] ?? FALSE,
      '#weight' => -10,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $cleaned_values = parent::massageFormValues($values, $form, $form_state);

    foreach ($values as $delta => $value) {
      $cleaned_values[$delta]['value']['show_shared'] = $values[$delta]['wrapper']['show_shared'];
    }

    return $cleaned_values;
  }

}
