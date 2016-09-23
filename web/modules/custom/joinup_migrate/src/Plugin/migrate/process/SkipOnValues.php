<?php

namespace Drupal\joinup_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateSkipRowException;

/**
 * Skips the whole row if source equals to one of the provided values.
 *
 * The "values" configuration key specifies an array of values for which
 * to skip the whole row.
 *
 * Examples:
 * @codingStandardsIgnoreStart
 *   plugin: skip_on_values
 *   values: 394
 *
 *   plugin: skip_on_values
 *   values:
 *     394
 *     5819
 *     84501
 * @codingStandardsIgnoreEnd
 *
 * @MigrateProcessPlugin(
 *   id = "skip_on_values"
 * )
 */
class SkipOnValues extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (is_array($value)) {
      throw new MigrateException('Multiple values for source are not supported.');
    }

    if (!array_key_exists('values', $this->configuration)) {
      throw new MigrateException('The value list must be specified');
    }

    $list = (array) $this->configuration['values'];
    if (array_search($value, $list) !== FALSE) {
      throw new MigrateSkipRowException();
    }

    return $value;
  }

}
