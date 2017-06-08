<?php

namespace Drupal\joinup_migrate;

use Drupal\migrate\Row;

/**
 * Interface for source plugins allowing migration of redirects.
 */
interface RedirectImportInterface {

  /**
   * Gets the redirect source given the source row.
   *
   * @param \Drupal\migrate\Row $row
   *   The migrate row object.
   *
   * @return array|null
   *   A redirect source as an associative array with two keys: 'path' and
   *  'query'. See \Drupal\redirect\Plugin\Field\FieldType\RedirectSourceItem
   *  for the meaning of the two values.
   *
   * @see \Drupal\redirect\Plugin\Field\FieldType\RedirectSourceItem
   */
  public function getRedirectSource(Row $row);

}
