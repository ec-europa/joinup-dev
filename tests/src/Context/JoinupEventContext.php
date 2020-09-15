<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Drupal\Core\Site\Settings;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\joinup\Traits\NodeTrait;

/**
 * Behat step definitions for testing events.
 */
class JoinupEventContext extends RawDrupalContext {

  use NodeTrait;

  /**
   * Keeps track of the number of Webtools Geocoding cache results that exist.
   *
   * @var int
   */
  protected static $webtoolsGeocodingCacheCount;

  /**
   * Navigates to the canonical page display of a event.
   *
   * @param string $title
   *   The name of the event.
   *
   * @When I go to the :title event
   * @When I visit the :title event
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function visitEvent(string $title): void {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->getNodeByTitle($title, 'event');
    $this->visitPath($node->toUrl()->toString());
  }

  /**
   * Converts relative dates into an accepted format.
   *
   * @param \Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope $scope
   *   An object containing the entity properties and fields that are to be used
   *   for creating the node as properties on the object.
   *
   * @throws \Exception
   *   Thrown when an invalid format is specified for the dates.
   *
   * @BeforeNodeCreate
   */
  public static function massageEventFieldsBeforeNodeCreate(BeforeNodeCreateScope $scope): void {
    $node = $scope->getEntity();
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
    $date_formatter = \Drupal::service('date.formatter');

    if ($node->type !== 'event') {
      return;
    }

    foreach (['field_event_date:value', 'field_event_date:end_value'] as $field) {
      if (isset($node->$field)) {
        $date = strtotime($node->$field);

        if ($date === FALSE) {
          throw new \Exception(sprintf('Invalid format for date specified: %s', $node->$field));
        }

        $node->$field = $date_formatter->format($date, 'custom', DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
      }
    }
  }

  /**
   * Ensures that the file cache for Geocoder results is enabled.
   *
   * We are using the Webtools Geocoding service for geocoding event addresses
   * so they can be displayed as maps. This service has a very limited monthly
   * quota so we should avoid requesting results just for testing purposes. We
   * are using the File Cache module to cache the results of the API calls to
   * the service, and these are being stored in the fixtures folder.
   *
   * @throws \Exception
   *   Thrown when the Webtools Geocoding results are not being cached.
   *
   * @BeforeScenario @api
   */
  public static function trackCachedFiles(): void {
    $cache_settings = Settings::get('cache');

    $geocoder_cache_backend = $cache_settings['bins']['geocoder'] ?? '';
    $geocoder_cache_directory = self::getWebtoolsGeocodingCacheDirectory();

    if ($geocoder_cache_backend !== 'cache.backend.file_system' || empty($geocoder_cache_directory)) {
      throw new \Exception('The Webtools Geocoding cache should be enabled. Please run ./vendor/bin/run drupal:settings site-clean');
    }

    // Keep track of the number of files that are present in the file cache.
    // After the feature has been completely tested we can check that the number
    // of files remains the same. This will ensure that no new requests have
    // been made to the Webtools Geocoding service without being saved in the
    // file cache.
    static::$webtoolsGeocodingCacheCount = self::getWebtoolsGeocodingCacheCount();
  }

  /**
   * Verifies that all requests to Webtools Geocoding are cached.
   *
   * @throws \Exception
   *   Thrown when live requests were done to the Webtools Geocoding service
   *   during the test.
   *
   * @AfterScenario @api
   */
  public static function checkCachedFiles(AfterScenarioScope $scope): void {
    $expected_count = self::$webtoolsGeocodingCacheCount;

    if ($expected_count !== NULL && $expected_count != self::getWebtoolsGeocodingCacheCount()) {
      $feature = $scope->getFeature()->getFile();
      throw new \Exception("$feature is doing live requests to the Webtools Geocoding service. Please make sure to commit all cached requests in tests/fixtures/webtools_geocoding_cache.");
    }
  }

  /**
   * Returns the number of Webtools Geocoding results that have been cached.
   *
   * @return int
   *   The number of cached results.
   */
  protected static function getWebtoolsGeocodingCacheCount(): int {
    $directory = self::getWebtoolsGeocodingCacheDirectory();
    return iterator_count(new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS));
  }

  /**
   * Returns the path to the Webtools Geocoding file cache.
   *
   * @return string
   *   The location of the treasure.
   */
  protected static function getWebtoolsGeocodingCacheDirectory(): string {
    $filecache_settings = Settings::get('filecache');
    return $filecache_settings['directory']['bins']['geocoder'] ?? '';
  }

}
