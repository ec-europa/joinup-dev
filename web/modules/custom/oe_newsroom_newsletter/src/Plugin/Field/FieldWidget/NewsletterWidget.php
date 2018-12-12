<?php

declare(strict_types = 1);

namespace Drupal\oe_newsroom_newsletter\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * The default field widget for the Newsroom newsletter field.
 *
 * @FieldWidget(
 *   id = "oe_newsroom_newsletter_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "oe_newsroom_newsletter"
 *   },
 * )
 */
class NewsletterWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\link\LinkItemInterface $item */
    $item = $items[$delta];

    $element['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable newsletter subscriptions'),
      '#default_value' => $item->get('enabled')->getValue(),
    ];

    $field_name = $items->getName();
    $parents = array_merge($element['#field_parents'], [
      $field_name,
      $delta,
      'enabled',
    ]);
    $enabled_field_name = array_shift($parents) . '[' . implode('][', $parents) . ']';
    $element['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Newsletter information'),
      '#states' => [
        'visible' => [
          ':input[name="' . $enabled_field_name . '"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['settings']['universe'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Universe acronym'),
      '#default_value' => $item->get('universe')->getValue(),
      '#validate' => [],
      '#states' => [
        'required' => [
          ':input[name="' . $enabled_field_name . '"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['settings']['service_id'] = [
      '#type' => 'number',
      '#title' => $this->t('Newsletter service ID'),
      '#default_value' => $item->get('service_id')->getValue(),
      '#states' => [
        'required' => [
          ':input[name="' . $enabled_field_name . '"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['#element_validate'][] = [get_called_class(), 'validateElement'];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      $value['enabled'] = (bool) $value['enabled'];
      $value['universe'] = $value['settings']['universe'];
      $value['service_id'] = (int) $value['settings']['service_id'];
      unset($value['settings']);
    }
    return $values;
  }

  /**
   * Form element validation handler.
   *
   * When the newsletter subscription is enabled, all fields should be filled
   * in.
   */
  public static function validateElement(&$element, FormStateInterface $form_state, $form) {
    if ($element['enabled']['#value'] == TRUE) {
      foreach (['universe', 'service_id'] as $field_id) {
        if (empty($element['settings'][$field_id]['#value'])) {
          $form_state->setError($element['settings'][$field_id], t('@title field is required when newsletter subscriptions are enabled.', ['@title' => $element['settings'][$field_id]['#title']]));
        }
      }
    }
  }

}
