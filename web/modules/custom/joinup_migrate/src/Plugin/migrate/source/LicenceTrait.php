<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\MigrateException;
use Drupal\migrate\Row;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Sets the translated licence for 'distribution' and 'document'.
 */
trait LicenceTrait {

  /**
   * The licence mapping.
   *
   * @var array
   */
  protected static $licenceMapping;

  /**
   * Translates the licence.
   *
   * @param \Drupal\migrate\Row $row
   *   The source row.
   * @param string $type
   *   The translation type, 'distribution' or 'document'.
   */
  protected function setLicence(Row $row, $type) {
    $columns = [
      'distribution' => 'D',
      'document' => 'E',
    ];

    $licence = $row->getSourceProperty('licence');
    if ($licence) {
      // Fill the licence mapping from the Excel table.
      $this->fillLicenceArray();
      // Scan for this value.
      foreach (static::$licenceMapping as $map) {
        if ($map[$columns[$type]] === $licence) {
          $row->setSourceProperty('licence', $map['A']);
          break;
        }
      }
    }
  }

  /**
   * Fills the licence mapping array.
   */
  protected function fillLicenceArray() {
    if (!isset(static::$licenceMapping)) {
      try {
        $file = '../resources/migrate/mapping-production.xlsx';

        // Identify the type of the input file.
        $file_type = IOFactory::identify($file);
        // Create a new Reader of the file type.
        /** @var \PhpOffice\PhpSpreadsheet\Reader\BaseReader $reader */
        $reader = IOFactory::createReader($file_type);
        // Advise the Reader that we only want to load cell data.
        $reader->setReadDataOnly(TRUE);
        // Advise the Reader of which worksheet we want to load.
        $reader->setLoadSheetsOnly('4. Licence Mapping');
        /** @var \PhpOffice\PhpSpreadsheet\Spreadsheet $workbook */
        $workbook = $reader->load($file);

        $worksheet = $workbook->getSheet();
      }
      catch (\Exception $e) {
        $class = get_class($e);
        throw new MigrateException("Got '$class', message '{$e->getMessage()}'.");
      }

      static::$licenceMapping = $worksheet->toArray(NULL, TRUE, TRUE, TRUE);
      // Drop the header row.
      unset(static::$licenceMapping[1]);
    }
  }

}
