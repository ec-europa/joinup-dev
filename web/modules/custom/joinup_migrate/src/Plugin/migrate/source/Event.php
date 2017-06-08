<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\joinup_migrate\HtmlUtility;
use Drupal\joinup_migrate\RedirectImportInterface;
use Drupal\migrate\Row;

/**
 * Migrates events.
 *
 * @MigrateSource(
 *   id = "event"
 * )
 */
class Event extends NodeBase implements RedirectImportInterface {

  use DefaultNodeRedirectTrait;
  use KeywordsTrait;
  use StateTrait;

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'start_date' => $this->t('Start date'),
      'end_date' => $this->t('End date'),
      'location' => $this->t('Location'),
      'organisation' => $this->t('Organisation'),
      'website' => $this->t('Website'),
      'mail' => $this->t('Contact mail'),
      'agenda' => $this->t('Agenda'),
      'scope' => $this->t('Scope'),
      'organisation_type' => $this->t('Organisation type'),
      'state' => $this->t('State'),
      'file_id' => $this->t('Logo ID'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_event', 'n')->fields('n', [
      'start_date',
      'end_date',
      'city',
      'venue',
      'address',
      'coord',
      'organisation',
      'website',
      'mail',
      'agenda',
      'state',
      'file_id',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $nid = $row->getSourceProperty('nid');
    $vid = $row->getSourceProperty('vid');

    // Build a geolocable location.
    $location = [];
    foreach (['city', 'venue', 'address', 'coord'] as $property) {
      $html = $row->getSourceProperty($property);
      if ($html = trim($html)) {
        $plain = trim(HtmlUtility::htmlToPlainText($html));
        // Clear inner empty lines.
        $plain = implode("\n", array_filter(array_map('trim', explode("\n", $plain))));
        if ($plain) {
          // Special treatment for 'coord' due to its inconsistent value. We
          // store it as @lat,long format make easily to be parsed later.
          if ($property === 'coord') {
            $plain = static::normaliseCoordinates($plain);
          }
          $location[] = $plain;
        }
      }
    }
    if ($location) {
      $row->setSourceProperty('location', implode("\n", $location));
    }

    // Keywords.
    $this->setKeywords($row, 'keywords', $nid, $vid);

    // Scope.
    $query = $this->select('term_node', 'tn');
    $query->join('term_data', 'td', 'tn.tid = td.tid');
    $scope = $query
      ->fields('td', ['name'])
      ->condition('tn.nid', $nid)
      ->condition('tn.vid', $vid)
      // The scope vocabulary vid is 45.
      ->condition('td.vid', 45)
      ->orderBy('td.name', 'ASC')
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('scope', $scope);

    // Organisation type.
    $query = $this->select('term_node', 'tn');
    $query->join('term_data', 'td', 'tn.tid = td.tid');
    $organisation_type = $query
      ->fields('td', ['name'])
      ->condition('tn.nid', $nid)
      ->condition('tn.vid', $vid)
      // The organisation type vocabulary vid is 63.
      ->condition('td.vid', 63)
      ->execute()
      ->fetchField();
    $row->setSourceProperty('organisation_type', $organisation_type ?: NULL);

    // State.
    $this->setState($row);

    // Attachments.
    $fids = $this->select('content_field_event_documentation', 'a')
      ->fields('a', ['field_event_documentation_fid'])
      ->condition('a.vid', $row->getSourceProperty('vid'))
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('fids', $fids);

    return parent::prepareRow($row);
  }

  /**
   * Tries to normalise the coordinates.
   *
   * @param string $plain
   *   Plain text that may contain coordinates.
   *
   * @return string
   *   The coordinates as '@lat,long' or the initial value as fallback.
   */
  public static function normaliseCoordinates($plain) {
    if (preg_match('|N?(\-?\d+(\.\d+)),\s*E?(\-?\d+(\.\d+))|', $plain, $found)) {
      return '@' . $found[1] . ',' . $found[3];
    }
    // Fallback to the input value.
    return $plain;
  }

}
