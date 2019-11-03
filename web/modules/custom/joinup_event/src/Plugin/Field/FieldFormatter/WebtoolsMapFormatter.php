<?php

declare(strict_types = 1);

namespace Drupal\joinup_event\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\oe_webtools_maps\Component\Render\JsonEncoded;
use Drupal\oe_webtools_maps\Plugin\Field\FieldFormatter\WebtoolsMapFormatter as OriginalWebtoolsMapFormatter;

/**
 * Displays a Geofield as a map using the Webtools Maps service.
 */
class WebtoolsMapFormatter extends OriginalWebtoolsMapFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = parent::viewElements($items, $langcode);

    // Set the render property to true and add a marker to the map.
    foreach ($items as $delta => $item) {
      $data_array = [
        'service' => 'map',
        'version' => '2.0',
        'render' => TRUE,
        'map' => [
          'zoom' => $this->getSetting('zoom_level'),
          'center' => [$item->get('lat')->getValue(), $item->get('lon')->getValue()],
        ],
      ];

      if (!empty($item->get('lat')->getValue()) && !empty($item->get('lon')->getValue())) {
        $data_array['layers'] = [
          'markers' => [
            'features' => [
              'geometry' => [
                'coordinates' => [
                  $item->get('lat')->getValue(),
                  $item->get('lon')->getValue(),
                ],
              ],
              'type' => 'Feature',
              'properties' => [],
            ],
            'type' => 'FeatureCollection',
          ],
          'options' => [
            'color' => 'red',
          ],
        ];
      }

      $element[$delta] = [
        '#type' => 'html_tag',
        '#tag' => 'script',
        '#value' => new JsonEncoded($data_array),
        '#attributes' => ['type' => 'application/json'],
      ];
    }
    return $element;
  }

}
