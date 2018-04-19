<?php

namespace Drupal\tallinn\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\LinkItemInterface;
use Drupal\tallinn\Plugin\Field\FieldType\TallinnEntryItem;

/**
 * Plugin implementation of the 'tallinn_entry_default' widget.
 *
 * This field is a complex field that includes a text area field with a format,
 * a link field and an option select field. Each of them will be constructed,
 * validated and contain widget similar or the same as their individual plugins.
 *
 * @see \Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextareaWidget
 * @see \Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget
 * @see \Drupal\link\Plugin\Field\FieldWidget\LinkWidget
 *
 * @FieldWidget(
 *   id = "tallinn_entry_default",
 *   label = @Translation("Tallinn entry widget"),
 *   field_types = {
 *     "tallinn_entry"
 *   }
 * )
 */
class TallinnEntryWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items[$delta];

    $wrapper_classes = 'js-form-wrapper form-wrapper';
    $element = [
      '#type' => 'details',
      '#title' => $this->fieldDefinition->getLabel() . ' - ' . $this->fieldDefinition->getDescription(),
      '#open' => TRUE,
    ];
    $element['#element_validate'][] = [get_called_class(), 'validateFormElement'];

    // The description should go on top.
    unset($element['#description']);
    $element['description'] = [
      '#markup' => $this->fieldDefinition->getDescription(),
      '#weight' => 0,
    ];

    $element['status'] = [
      '#type' => 'select',
      '#title' => $this->t('Implementation status'),
      '#options' => TallinnEntryItem::getStatusOptions(),
      '#default_value' => $item->status,
      '#weight' => 1,
      '#prefix' => '<div class="' . $wrapper_classes . '">',
      '#suffix' => '</div>',
    ];

    $element['explanation'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Explanations'),
      '#default_value' => $item->value,
      '#format' => $item->format,
      '#weight' => 2,
      '#prefix' => '<div class="' . $wrapper_classes . '">',
      '#suffix' => '</div>',
    ];

    $element['uri'] = [
      '#type' => 'url',
      '#title' => $this->t('Related website'),
      '#default_value' => $item->uri,
      '#maxlength' => 2048,
      // Only external links, i.e. full links.
      '#link_type' => LinkItemInterface::LINK_EXTERNAL,
      '#weight' => 3,
      '#prefix' => '<div class="' . $wrapper_classes . '">',
      '#suffix' => '</div>',
    ];

    return $element;
  }

  /**
   * Form element validation handler for the complete form element.
   */
  public static function validateFormElement($element, FormStateInterface $form_state, $form) {
    $status = $element['status']['#value'];
    $explanation = $element['explanation']['value']['#value'];
    if (in_array($status, ['in_progress', 'completed']) && empty($explanation)) {
      $arguments = [
        '@title' => $element['#title'],
        '%status' => TallinnEntryItem::getStatusOptions()[$status],
      ];
      $form_state->setError($element['explanation']['value'], t('@title: <em>Explanations</em> field is required when the status is %status.', $arguments));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $values = parent::massageFormValues($values, $form, $form_state);
    $values = reset($values);
    if (!empty($values['explanation']['value'])) {
      $values += $values['explanation'];
    }
    unset($values['explanation']);

    // In case the uri field is not filled, unset the value because an empty
    // string will throw a primitive value issue.
    if (empty($values['uri'])) {
      unset($values['uri']);
    }

    return $values;
  }

}
