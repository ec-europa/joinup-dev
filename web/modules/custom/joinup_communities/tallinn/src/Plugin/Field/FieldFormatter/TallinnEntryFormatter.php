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

    $color = $this->getOptionToColor($item);
    $option = $this->getOptionToString($item);
    $font_color = in_array($color, ['red', 'green', 'grey']) ? 'white' : 'black';
    $build = [
      '#theme' => 'tallinn_entry',
      '#title' => $this->fieldDefinition->getLabel(),
      '#status' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $option,
        '#attributes' => [
          'style' => "background-color: {$color}; color: {$font_color}",
        ],
        '#weight' => 0,
      ],
    ];

    if ($value = $item->getValue()['value']) {
      $build['#explanation'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $item->getValue()['value'],
      ];
    }

    if (!empty($item->getValue()['uri'])) {
      $uri = $item->getValue()['uri'];
      $build['#url'] = [
        '#type' => 'link',
        '#url' => Url::fromUri($uri),
        '#title' => $uri,
      ];
    }

    $build['#cache']['max-age'] = 0;

    return $build;
  }

  /**
   * Returns the corresponding color of the selected status option.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $item
   *   The field item.
   *
   * @return string
   *   The corresponding color or transparent in case no value exists.
   */
  protected function getOptionToColor(TypedDataInterface $item) {
    $option_colors = [
      'no_data' => 'grey',
      'no_progress' => 'red',
      'in_progress' => 'yellow',
      'completed' => 'green',
    ];

    if (!empty($item->getValue()['status'])) {
      return $option_colors[$item->getValue()['status']];
    }

    return 'rgba(0, 0, 0, 0)';
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
