<?php

namespace Drupal\tallinn\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\Url;
use Drupal\tallinn\Plugin\Field\FieldType\TallinnEntryItem;

/**
 * Plugin implementation of the 'tallinn_entry' formatter.
 *
 * @FieldFormatter(
 *   id = "tallinn_entry",
 *   label = @Translation("Complex tallinn formatter"),
 *   field_types = {
 *     "tallinn_entry"
 *   }
 * )
 */
class TallinnEntryFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'trim_length' => '80',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['trim_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Trim link text length'),
      '#field_suffix' => $this->t('characters'),
      '#default_value' => $this->getSetting('trim_length'),
      '#min' => 1,
      '#description' => $this->t('Leave blank to allow unlimited link text lengths.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($trim_length = $this->getSetting('trim_length')) {
      $summary[] = $this->t('Link text trimmed to @limit characters', ['@limit' => $trim_length]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // All fields are single-value fields.
    $item = $items->first();
    if (empty($item)) {
      return [];
    }

    $value = $item->getValue();
    $element = [
      '#theme' => 'tallinn_entry_formatter',
      '#title' => $this->fieldDefinition->getLabel() . ' - ' . $this->fieldDefinition->getDescription(),
    ];

    $element['#status'] = [
      '#type' => 'container',
      'value' => [
        '#markup' => $this->getOptionToString($item),
      ],
      '#attributes' => [
        'class' => $this->getOptionClasses($item),
      ],
    ];

    if (!empty($value['value'])) {
      $element['#explanation'] = [
        '#type' => 'processed_text',
        '#title' => $this->t('Explanation'),
        '#text' => $value['value'],
        '#format' => $value['format'],
      ];
    }

    if (!empty($value['uri'])) {
      $link_title = $value['uri'];

      // Trim the link text to the desired length.
      if ($trim_length = $this->getSetting('trim_length')) {
        $link_title = Unicode::truncate($value['uri'], $trim_length, FALSE, TRUE);
      }

      $element['#uri'] = [
        '#type' => 'link',
        '#url' => Url::fromUri($value['uri']),
        '#title' => $link_title,
      ];
    }

    return $element;
  }

  /**
   * Returns the classes related to the selected status option.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $item
   *   The field item.
   *
   * @return array
   *   An array of classes to pass to the status area.
   */
  protected function getOptionClasses(TypedDataInterface $item) {
    $option_class = 'tallinn--' . $item->getValue()['status'];
    return ['tallinn', 'tallinn__message', $option_class];
  }

  /**
   * Returns the human readable name of the status option.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $item
   *   The entry item.
   *
   * @return string
   *   The human readable version of the status option.
   */
  protected function getOptionToString(TypedDataInterface $item) {
    $options = TallinnEntryItem::getStatusOptions();
    $option = $item->getValue()['status'];
    return $options[$option];
  }

}
