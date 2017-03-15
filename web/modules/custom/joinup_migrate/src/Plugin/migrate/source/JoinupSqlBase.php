<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Site\Settings;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Provides a base class for SqlBase classes.
 */
abstract class JoinupSqlBase extends SqlBase {

  /**
   * A list of source objects that should be checked for existing URIs.
   *
   * Migration source plugin classes should implement this property to declare
   * a list of tables/views that should be checked for the 'uri' field in order
   * to build an 'already taken' URI list.
   *
   * @var string[]
   *
   * For example 'solution' migration might want to set this property as:
   * @code
   * protected $reservedUriTables = ['collection'];
   * @endcode
   * In this way, the migration will prohibit using URIs that are already
   * present in the field 'uri' of the source table 'd8_collection'. TL;DR:
   * Solutions cannot have URIs (as IDs) that are already in Collections.
   *
   * Note that array items should not have the 'd8_' prefix.
   */
  protected $reservedUriTables = [];

  /**
   * A list of source properties representing URIs to be normalised.
   *
   * Such fields are checked if they are valid URIs and if they don't point to
   * the Drupal 6 old URLs.
   *
   * @var string[]
   */
  protected $uriProperties = ['uri'];

  /**
   * Gets the legacy site webroot directory.
   *
   * @return string
   *   The legacy site webroot directory
   *
   * @throws \Drupal\migrate\MigrateException
   *   When the webroot was not configured.
   */
  protected function getLegacySiteWebRoot() {
    $webroot = Settings::get('joinup_migrate.source.root');

    if (empty($webroot)) {
      throw new MigrateException('The web root of the D6 site is not configured. Please run `phing setup-migration`.');
    }

    return rtrim($webroot, '/');
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if ($this->reservedUriTables && $row->hasSourceProperty('uri') && ($uri = $row->getSourceProperty('uri'))) {
      $reserved = $this->getUrisToExclude();
      if (in_array($uri, $reserved)) {
        // This URI is in the reserved list. Generate a new one.
        $row->setSourceProperty('uri', NULL);
      }
    }

    // Normalize URIs.
    foreach ($this->uriProperties as $property) {
      if ($row->hasSourceProperty($property)) {
        $uri = $row->getSourceProperty($property);
        $row->setSourceProperty($property, $this->normalizeUri($uri));
      }
    }

    return parent::prepareRow($row);
  }

  /**
   * Builds a list of URIs to be forbidden in the current migration.
   *
   * @return string[]
   *   A list of URIs.
   */
  protected function getUrisToExclude() {
    static $cache = [];

    $uri = [];

    foreach ($this->reservedUriTables as $table) {
      $table = "d8_$table";
      if (!isset($cache[$table])) {
        if ($this->getDatabase()->schema()->tableExists($table) && $this->getDatabase()->schema()->fieldExists($table, 'uri')) {
          $cache[$table] = $this->select($table)
            ->fields($table, ['uri'])
            ->isNotNull('uri')
            ->execute()
            ->fetchCol();
        }
      }
      $uri = array_merge($uri, array_diff($cache[$table], $uri));
    }

    return $uri;
  }

  /**
   * Normalizes an URI.
   *
   * It checks if the URI has the correct pattern and doesn't point to the old
   * Drupal 6 site. If the validation fails, it returns NULL.
   *
   * @param string $uri
   *   The URI to be normalized.
   *
   * @return string|null
   *   The normalized URI or NULL.
   */
  protected function normalizeUri($uri) {
    $uri = trim($uri);

    if (empty($uri)) {
      return NULL;
    }

    // Don't allow malformed URIs.
    if (!$url = parse_url($uri)) {
      return NULL;
    }

    if (empty($url['scheme'])) {
      // Needs a full-qualified URL. The URI might be 'www.example.com'.
      $uri = "http://$uri";
    }

    // Check for a valid URI pattern.
    if (!UrlHelper::isValid($uri, TRUE)) {
      return NULL;
    }

    // Don't allow empty host or old Joinup host.
    if (empty($url['host']) || $url['host'] === 'joinup.ec.europa.eu') {
      return NULL;
    }

    return $uri;
  }

}
