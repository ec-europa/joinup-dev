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
    // The only difference with the parent method is that we add the `render`
    // property to force the map to render.
    foreach ($items as $delta => $item) {
      $element[$delta]['#value'] = new JsonEncoded([
        'service' => 'map',
        'version' => '2.0',
        'render' => TRUE,
        'map' => [
          'zoom' => $this->getSetting('zoom_level'),
          'center' => [$item->get('lat')->getValue(), $item->get('lon')->getValue()],
        ],
      ]);
    }
    return $element;
  }

}
