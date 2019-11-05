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
        $entity = $item->getEntity();
        // Normally, this should always has a value since the coordinated derive
        // from the field_location. However, to protect from a site break on
        // possible future updates, we perform a check.
        $description = $entity->hasField('field_location') && !empty($entity->field_location->value) ? $entity->field_location->value : '';

        $data_array['layers'] = [
          [
            'markers' => [
              'type' => 'FeatureCollection',
              'features' => [
                [
                  'type' => 'Feature',
                  'properties' => [
                    'name' => $entity->label(),
                    'description' => $description,
                  ],
                  'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [
                      // According to OE team, while every instance of longitude
                      // (lon) and latitude (lat) values are lat first and lon
                      // second, for the marker coordinates we need to set them
                      // in a reverse way as the normal way is the US standard
                      // and in our API it translates the values wrongly making
                      // the marker appear in the middle of the full map
                      // (apparently the default values apply).
                      $item->get('lon')->getValue(),
                      $item->get('lat')->getValue(),
                    ],
                  ],
                ],
              ],
            ],
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
