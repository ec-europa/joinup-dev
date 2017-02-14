<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Component\Utility\UrlHelper;
use Drupal\migrate\Row;

/**
 * Prepares an URI.
 */
trait UriTrait {

  /**
   * Normalizes an imported URI.
   *
   * @param string $name
   *   The property name.
   * @param \Drupal\migrate\Row $row
   *   The migration row.
   * @param bool $allow_internal
   *   Allow internal URI.
   */
  protected function normalizeUri($name, Row $row, $allow_internal = TRUE) {
    if ($uri = $row->getSourceProperty($name)) {
      // Don't import malformed URLs.
      if (!UrlHelper::isValid($uri)) {
        $row->setSourceProperty($name, NULL);
        return;
      }
      $url = parse_url($uri);
      if (empty($url['scheme'])) {
        // Needs a full-qualified URL.
        $uri = "http://$uri";
      }

      if (!$allow_internal && !empty($url['host']) && $url['host'] === 'joinup.ec.europa.eu') {
        $row->setSourceProperty($name, NULL);
        return;
      }

      $row->setSourceProperty($name, $uri);
    }
  }

}
