<?php

declare(strict_types = 1);

namespace Drupal\joinup_event\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
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

    foreach ($items as $delta => $item) {
      /** @var \Drupal\oe_webtools_maps\Component\Render\JsonEncoded $json */
      $json = $element[$delta]['#value'];
      $json_data = $json->getJson();
      // The render property forces the map to appear on page load and not on
      // scroll.
      $json_data['render'] = TRUE;

      $entity = $item->getEntity();
      // Normally, this should always has a value since the coordinated derive
      // from the field_location. However, to protect from a site break on
      // possible future updates, we perform a check.
      $description = $entity->hasField('field_location') && !empty($entity->field_location->value) ? $entity->field_location->value : '';
      $json_data['layers'][0]['markers']['features'][0]['properties']['name'] = $entity->label();
      $json_data['layers'][0]['markers']['features'][0]['properties']['description'] = $description;
      $json->setJson($json_data);
    }

    $json = $element[0]['#value'];
    $json->setJson($json->getJson() + ['render' => TRUE]);

    return $element;
  }

}
