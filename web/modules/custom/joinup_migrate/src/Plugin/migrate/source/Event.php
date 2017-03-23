<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
use Drupal\migrate\Row;

/**
 * Migrates events.
 *
 * @MigrateSource(
 *   id = "event"
 * )
 */
class Event extends NodeBase {

  use KeywordsTrait;

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
        $plain = trim(static::htmlToPlainText($html));
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

    return parent::prepareRow($row);
  }

  /**
   * Transforms an HTML string into plain text.
   *
   * Inspired from \Drupal\Core\Mail\MailFormatHelper::htmlToText().
   *
   * @param string $html
   *   The HTML markup to be converted.
   *
   * @return string
   *   The plain text version.
   *
   * @see \Drupal\Core\Mail\MailFormatHelper::htmlToText()
   */
  protected static function htmlToPlainText($html) {
    // Ensure tags, entities, attributes are well-formed and properly nested.
    $html = Html::normalize(Xss::filter($html, static::$supportedTags));

    // Split tags from text.
    $split = preg_split('/<([^>]+?)>/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

    // Note: PHP ensures the array consists of alternating delimiters and
    // literals and begins and ends with a literal (inserting NULL as required).
    // Odd/even counter (tag or no tag).
    $is_tag = FALSE;
    $output = '';

    foreach ($split as $value) {
      // Holds a string ready to be formatted and output.
      $chunk = NULL;

      // Process HTML tags (but don't output any literally).
      if ($is_tag) {
        list($tagname) = explode(' ', strtolower($value), 2);
        if (preg_match('@^\/[blockquote|dd|dl|li|ul|ul|h1|h2|h3|h4|h5|h6|div|p]@i', $tagname)) {
          // Ensure blank new-line.
          $chunk = '';
        }
      }
      // Process blocks of text.
      else {
        $value = trim(Html::decodeEntities($value));
        // Non-breaking space to normal space.
        $value = str_replace(chr(194) . chr(160), ' ', $value);
        if (Unicode::strlen($value)) {
          $chunk = $value;
        }
      }

      // See if there is something waiting to be output.
      if (isset($chunk)) {
        // Append a newline char.
        $output .= "$chunk\n";
      }

      $is_tag = !$is_tag;
    }

    // Unify line-endings to Unix style. DOS to Unix.
    $output = str_replace("\r\n", "\n", $output);
    // Old Mac OS to Unix.
    $output = str_replace("\r", "\n", $output);

    return $output;
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

  /**
   * Supported tags in HTML to plain text conversion.
   *
   * @var string[]
   */
  protected static $supportedTags = [
    'br',
    'p',
    'div',
    'blockquote',
    'ul',
    'ol',
    'li',
    'dl',
    'dt',
    'dd',
    'h1',
    'h2',
    'h3',
    'h4',
    'h5',
    'h6',
  ];

}
