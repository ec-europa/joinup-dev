<?php

namespace Drupal\joinup_migrate\Plugin\migrate\process;

use Drupal\joinup_migrate\HtmlUtility;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides a processor that converts HTML to plain text.
 *
 * @MigrateProcessPlugin(
 *   id = "html_to_plain_text"
 * )
 */
class HtmlToPlainText extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return HtmlUtility::htmlToPlainText($value);
  }

}
