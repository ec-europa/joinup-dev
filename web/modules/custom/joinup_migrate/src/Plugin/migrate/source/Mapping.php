<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Component\Utility\Unicode;
use Drupal\migrate\MigrateException;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Provides the 'mapping' source plugin.
 *
 * @MigrateSource(
 *   id = "mapping"
 * )
 */
class Mapping extends TestableSpreadsheetBase {

  /**
   * The collection list.
   *
   * @var string[]
   */
  protected $collections;

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
  protected function rowIsValid(array &$row) {
    // If this row is not migrated, exit now.
    if ($row['Migrate'] !== 'Yes') {
      return FALSE;
    }

    $messages = [];
    $title = $type = NULL;

    $nid = $row['Nid'];
    $collection = $row['Collection_Name'] = trim((string) $row['Collection_Name']);

    if (empty($collection)) {
      $messages[] = 'Collection name empty';
    }
    elseif (!in_array($collection, $this->getCollections())) {
      $messages[] = "Collection doesn't exist";
    }

    if (!is_numeric($nid)) {
      $node = NULL;
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

    $declared_type = static::$typeMapping[Unicode::strtolower($row['Type of content item'])];

    if (!empty($node) && ($type !== $declared_type)) {
      $messages[] = "Type '{$row['Type of content item']}' declared, but nid $nid is '$type' in Drupal 6";
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

    if (!empty($row['Collection state']) && !in_array($row['Collection state'], ['validated', 'archived'])) {
      $messages[] = "Invalid 'Collection state': '{$row['Collection state']}' (allowed empty or 'validated' or 'archived')";
    }

    // Register inconsistencies.
    if ($messages) {
      $row_index = $row['row_index'];
      $source_ids = ['Nid' => $row['Nid']];
      foreach ($messages as $message) {
        $this->migration->getIdMap()->saveMessage($source_ids, "Row: $row_index, Nid: $nid: $message");
      }
    }

    return empty($messages);
  }

  /**
   * Returns a complete list of collections.
   *
   * @return string[]
   *   An indexed list of collections.
   *
   * @throws \Drupal\migrate\MigrateException
   *   When is not able to parse the worksheet.
   */
  protected function getCollections() {
    if (!isset($this->collections)) {
      try {
        $file = $this->configuration['file'];

        // Identify the type of the input file.
        $file_type = IOFactory::identify($file);
        // Create a new Reader of the file type.
        /** @var \PhpOffice\PhpSpreadsheet\Reader\BaseReader $reader */
        $reader = IOFactory::createReader($file_type);
        // Advise the Reader that we only want to load cell data.
        $reader->setReadDataOnly(TRUE);
        // Advise the Reader of which worksheet we want to load.
        $reader->setLoadSheetsOnly('5. Collections');
        /** @var \PhpOffice\PhpSpreadsheet\Spreadsheet $workbook */
        $workbook = $reader->load($file);

        $worksheet = $workbook->getSheet();
      }
      catch (\Exception $e) {
        $class = get_class($e);
        throw new MigrateException("Got '$class', message '{$e->getMessage()}'.");
      }

      $cells = $worksheet->toArray(NULL, TRUE, TRUE, TRUE);
      // Drop the header row.
      unset($cells[1]);

      $this->collections = array_values(array_map(function (array $row) {
        return $row['T'];
      }, $cells));
    }
    return $this->collections;
  }

  /**
   * Map between Excel file declared types and Drupal 6 real types.
   *
   * @var array
   */
  protected static $typeMapping = [
    'case' => 'case_epractice',
    'community' => 'community',
    'document' => 'document',
    'event' => 'event',
    'factsheet' => 'factsheet',
    'interoperability solution' => 'asset_release',
    'legal document' => 'legaldocument',
    'news' => 'news',
    'newsletter' => 'newsletter',
    'presentation' => 'presentation',
    'project' => 'project_project',
    'repository' => 'repository',
  ];

}
