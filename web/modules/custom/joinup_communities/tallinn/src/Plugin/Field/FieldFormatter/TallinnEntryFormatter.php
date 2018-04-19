<?php

namespace Drupal\tallinn\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
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
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // All fields are single-value fields.
    $item = $items->first();
    if (empty($item)) {
      return [];
    }

    $value = $item->getValue();
    $classes = $this->getOptionClasses($item);
    $option = $this->getOptionToString($item);
    $build = [
      '#theme' => 'tallinn_entry',
    ];

    $build['#title'] = $this->fieldDefinition->getLabel();
    $build['#description'] = $this->fieldDefinition->getDescription();
    $build['#status'] = [
      '#markup' => $option,
      '#attributes' => [
        'class' => $classes,
      ],
    ];

    if (!empty($value['value'])) {
      $build['#explanation'] = [
        '#title' => t('Explanation'),
        '#title_display' => 'inline',
        '#type' => 'processed_text',
        '#text' => $value['value'],
        '#format' => $value['format'],
      ];
    }

    if (!empty($value['uri'])) {
      $build['#url'] = [
        '#type' => 'link',
        '#url' => Url::fromUri($value['uri']),
        '#title' => $value['uri'],
      ];
    }

    return $build;
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
