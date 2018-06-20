<?php

namespace Drupal\joinup_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * This plugin merges arrays together.
 *
 * Inspired from https://www.drupal.org/project/migrate_plus. The difference
 * consists in adding an 'unique' config and casting the values to array.
 *
 * @see https://www.drupal.org/project/migrate_plus
 *
 * @MigrateProcessPlugin(
 *   id = "merge"
 * )
 */
class Merge extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value)) {
      throw new MigrateException('Input should be an array.');
    }
    $new_value = [];
    foreach ($value as $item) {
      $new_value = array_merge($new_value, (array) $item);
    }

    if (!empty($this->configuration['unique'])) {
      $new_value = array_unique($new_value);
    }

    return $new_value;
  }

}
