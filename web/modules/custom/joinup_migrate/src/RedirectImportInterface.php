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
   * @return array[]|null
   *   A list of redirect sources an an indexed array of associative arrays,
   *   each one having two keys: 'path' and 'query'. See RedirectSourceItem for
   *   the meaning of the two values.
   *
   * @see \Drupal\redirect\Plugin\Field\FieldType\RedirectSourceItem
   */
  public function getRedirectSources(Row $row);

}
