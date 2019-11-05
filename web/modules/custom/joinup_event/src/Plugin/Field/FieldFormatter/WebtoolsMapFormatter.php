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

    /** @var \Drupal\oe_webtools_maps\Component\Render\JsonEncoded $json */
    $json = $element[0]['#value'];
    // The render property forces the map to appear on page load and not on
    // scroll.
    $json->setJson($json->getJson() + ['render' => TRUE]);

    return $element;
  }

}
