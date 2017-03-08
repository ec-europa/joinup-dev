<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Site\Settings;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_spreadsheet\Plugin\migrate\source\Spreadsheet;

/**
 * Provides a wrapper around Spreadsheet migrate source plugin.
 *
 * Wrap the original Spreadsheet migrate source in order to allow switching
 * the mode between 'production' and 'test'. The switch between the two modes
 * is made by setting the setting 'joinup_migrate.mode' either to 'production'
 * or to 'test'. This is done by editing the 'build.properties.local', setting
 * the property Set the 'migration.mode' either to 'production' or to test' and
 * then running `phing setup-migration`.
 *
 * @see \Drupal\migrate_spreadsheet\Plugin\migrate\source\Spreadsheet
 *
 * @MigrateSource(
 *   id = "mapping"
 * )
 */
class Mapping extends Spreadsheet {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    // Allow switching between 'production' and 'test' mode.
    $mode = Settings::get('joinup_migrate.mode');
    if (!$mode || !in_array($mode, ['production', 'test'])) {
      throw new MigrateException("The settings.php setting 'joinup_migrate.mode' is not configured or is invalid (should be 'production' or 'test'). Please run `phing setup-migration`.");
    }

    $configuration['file'] = "../resources/migrate/mapping-$mode.xlsx";

    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $collection = $row->getSourceProperty('Collection_Name');

    if (empty($collection) || $collection === '#N/A') {
      $row_index = $row->getSourceProperty('row_index');
      $nid = $row->getSourceProperty('Nid');

      $this->migration->getIdMap()->saveMessage(['Nid' => $nid], "Row #$row_index: Collection name empty or invalid.");
    }

    return parent::prepareRow($row);
  }

}
