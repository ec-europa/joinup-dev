<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Database;
use Drupal\Core\Site\Settings;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrationInterface;
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
   * Connection to source database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'type' => $this->t('Node type'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    $this->db = Database::getConnection('default', 'migrate');

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
  public function initializeIterator() {
    /** @var \Drupal\migrate_spreadsheet\SpreadsheetIteratorInterface $iterator */
    $iterator = parent::initializeIterator();

    $iterator->rewind();
    $rows = [];
    while ($iterator->valid()) {
      $row = $iterator->current();
      if ($this->rowIsValid($row)) {
        $rows[] = $row;
      }
      $iterator->next();
    }

    return new \ArrayIterator($rows);
  }

  /**
   * Checks if a row is valid and logs all inconsistencies.
   *
   * @param array $row
   *   The row to be checked. The $row array can be altered.
   *
   * @return bool
   *   If the row is valid.
   */
  protected function rowIsValid(array &$row) {
    $messages = [];

    $nid = $row['nid'];
    $row['Collection_Name'] = trim((string) $row['Collection_Name']);

    $title = $type = NULL;
    if (in_array($row['Collection_Name'], ['', '#N/A'], TRUE)) {
      $messages[] = 'Collection name empty or invalid';
    }
    if (!is_numeric($nid)) {
      $messages[] = "Invalid nid '$nid'";
    }
    else {
      $node = $this->db->select('node')
        ->fields('node', ['title', 'type'])
        ->condition('nid', $nid)
        ->execute()
        ->fetch();
      if (!$node) {
        $messages[] = "This node doesn't exist in the source database";
      }
      else {
        $title = $node->title;
        $type = $node->type;
      }
    }

    $row['type'] = $type;

    if ($row['type'] === 'asset_release') {
      // Check for 'asset_release' acting as 'release'.
      /** @var \Drupal\Core\Database\Query\SelectInterface $query */
      $query = $this->db->select('og_ancestry', 'o')
        ->fields('o', ['nid'])
        ->condition('o.nid', (int) $nid)
        ->condition('g.type', 'project_project');
      $query->join('node', 'g', 'o.group_nid = g.nid');
      // Is release.
      if ($query->execute()->fetchField()) {
        $messages[] = "'$title' is a release and shouldn't be in the Excel file. Releases are computed";
      }
    }
    elseif ($row['type'] === 'project') {
      $messages[] = "Software (project) content should not be in the Excel file. Replace with Project (project_project)";
    }

    if (!in_array($row['New collection'], ['Yes', 'No'])) {
      $messages[] = "Invalid 'New Collection': '{$row['New collection']}'";
    }

    if (!empty($row['Collection state']) && !in_array($row['Collection state'], ['validated', 'archived'])) {
      $messages[] = "Invalid 'Collection state': '{$row['Collection state']}' (allowed empty or 'validated' or 'archived')";
    }

    // Register inconsistencies.
    if ($messages) {
      $row_index = $row['row_index'];
      $source_ids = ['nid' => $row['nid']];
      foreach ($messages as $message) {
        $this->migration->getIdMap()->saveMessage($source_ids, "Row: $row_index, Nid: $nid: $message");
      }
    }

    return empty($messages);
  }

}
