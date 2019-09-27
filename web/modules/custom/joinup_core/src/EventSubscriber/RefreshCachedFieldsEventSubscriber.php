<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\EventSubscriber;

use Drupal\cached_computed_field\Event\RefreshExpiredFieldsEventInterface;
use Drupal\cached_computed_field\EventSubscriber\RefreshExpiredFieldsSubscriberBase;
use Drupal\cached_computed_field\ExpiredItemCollection;
use Drupal\cached_computed_field\ExpiredItemInterface;
use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\matomo_reporting_api\MatomoQueryFactoryInterface;
use Matomo\ReportingApi\QueryInterface;

/**
 * Event subscriber that updates counters with data drawn from Matomo.
 *
 * Data currently include the download count for distributions and visit count
 * for nodes.
 */
class RefreshCachedFieldsEventSubscriber extends RefreshExpiredFieldsSubscriberBase {

  use StringTranslationTrait;

  /**
   * The name of the field that contains download counts.
   *
   * @var string
   */
  const FIELD_NAME_DOWNLOAD_COUNT = 'field_download_count';

  /**
   * The name of the field that contains visit counts.
   *
   * @var string
   */
  const FIELD_NAME_VISIT_COUNT = 'field_visit_count';

  /**
   * The Matomo query factory.
   *
   * @var \Drupal\matomo_reporting_api\MatomoQueryFactoryInterface
   */
  protected $matomoQueryFactory;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The Matomo settings config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $matomoSettings;

  /**
   * Constructs a new RefreshCachedMatomoDataEventSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The system time service.
   * @param \Drupal\matomo_reporting_api\MatomoQueryFactoryInterface $matomoQueryFactory
   *   The Matomo query factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, TimeInterface $time, MatomoQueryFactoryInterface $matomoQueryFactory, ConfigFactoryInterface $configFactory, LoggerChannelFactoryInterface $loggerFactory) {
    parent::__construct($entityTypeManager, $time);
    $this->matomoQueryFactory = $matomoQueryFactory;
    $this->configFactory = $configFactory;
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function refreshExpiredFields(RefreshExpiredFieldsEventInterface $event) {
    $items = $event->getExpiredItems();
    $items = $this->filterInvalidItems($items);

    $query = $this->buildQuery($items);
    try {
      $response = $query->execute()->getResponse();
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('joinup_core')->error($e->getMessage());
      return;
    }

    foreach ($items as $index => $expired_item) {
      $bundle = $this->getEntity($expired_item)->bundle();
      $type = $this->getType($bundle);
      $response_item = $response[$index];
      $count = 0;
      foreach ($response_item as $result) {
        if (!empty($result->$type)) {
          $count = $count + (int) $result->$type;
        }
      }

      $this->updateFieldValue($expired_item, $count);
    }
  }

  /**
   * Gets the correct URL parameter for the query.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that the request url is related to.
   *
   * @return string|null
   *   The url for the request or NULL if no url is detected.
   */
  protected function getUrlParameter(EntityInterface $entity): ?string {
    $bundle = $entity->bundle();
    switch ($this->getMethod($bundle)) {
      case 'download_counts':
        $url = $this->getFileUrl($entity);
        break;

      default:
        $url = $this->getEntityUrl($entity);
        break;
    }

    // Currently, the only case where false will be returned is if a
    // distribution does not have a file attached. This is against ADMS
    // specifications but might still occur since there are entities migrated
    // and in the future also federated.
    return $url ?? NULL;
  }

  /**
   * Returns the time period that is configured for the given bundle.
   *
   * @param string $bundle
   *   The bundle for which to retrieve the time period.
   *
   * @return int
   *   The time period in days, or 0 for all time. Defaults to 0.
   */
  protected function getTimePeriod(string $bundle): int {
    $settings = $this->getMatomoSettings($bundle);
    return empty($settings['period']) ? 0 : (int) $settings['period'];
  }

  /**
   * Returns the action type that is configured for the given bundle.
   *
   * @param string $bundle
   *   The bundle for which to retrieve the action type.
   *
   * @return string
   *   The action type to retrieve, as a parameter to be used in a Matomo API
   *   call. Defaults to 'nb_hits'.
   */
  protected function getType(string $bundle): string {
    $settings = $this->getMatomoSettings($bundle);
    return empty($settings['type']) ? 'nb_hits' : $settings['type'];
  }

  /**
   * Returns the method that is configured for the given bundle.
   *
   * @param string $bundle
   *   The bundle for which to retrieve the API method.
   *
   * @return string
   *   The method. Can be either 'download_counts' or 'visit_counts'. Defaults
   *   to 'visit_counts'.
   */
  protected function getMethod(string $bundle): string {
    $settings = $this->getMatomoSettings($bundle);
    return empty($settings['method']) ? 'visit_counts' : $settings['method'];
  }

  /**
   * Returns the Matomo API method that applies to the given bundle.
   *
   * @param string $bundle
   *   The bundle for which to retrieve the API method.
   *
   * @return string
   *   The Matomo API method. Defaults to 'Actions.getPageUrl'.
   */
  protected function getMatomoMethod(string $bundle): string {
    $method = $this->getMethod($bundle);
    return $method === 'download_counts' ? 'Actions.getDownload' : 'Actions.getPageUrl';
  }

  /**
   * Returns the Matomo URL parameter name that applies to the given bundle.
   *
   * @param string $bundle
   *   The bundle for which to retrieve the URL parameter name.
   *
   * @return string
   *   The URL parameter name. Defaults to 'pageUrl'.
   */
  protected function getUrlParameterName(string $bundle): string {
    $method = $this->getMethod($bundle);
    return $method === 'download_counts' ? 'downloadUrl' : 'pageUrl';
  }

  /**
   * Returns the absolute URL to the canonical page of the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to return the URL.
   *
   * @return string
   *   The entity URL.
   */
  protected function getEntityUrl(EntityInterface $entity): string {
    return $entity->toUrl()->setAbsolute()->toString();
  }

  /**
   * Generates an argument array that represents the time range.
   *
   * @param int $period
   *   The starting period represented by the number of days ago.
   *
   * @return array
   *   An array with the starting and ending time range.
   */
  protected function getDateRange(int $period): array {
    return [
      // If the period is 0 we should get all results since launch.
      $period > 0 ? (new DateTimePlus("$period days ago"))->format('Y-m-d') : $this->configFactory->get('joinup_core.matomo_settings')->get('launch_date'),
      (new DateTimePlus())->format('Y-m-d'),
    ];
  }

  /**
   * Filters out invalid entries from a collection of items.
   *
   * Invalid entries can be items that their entities don't exist or have not
   * expired yet.
   *
   * @param \Drupal\cached_computed_field\ExpiredItemCollection $items
   *   A collection of items.
   *
   * @return \Drupal\cached_computed_field\ExpiredItemCollection
   *   The filtered collection of items.
   */
  protected function filterInvalidItems(ExpiredItemCollection $items): ExpiredItemCollection {
    $valid_items = [];
    foreach ($items as $item) {
      if (!$entity = $this->getEntity($item)) {
        continue;
      }

      // Only refresh the field if it has actually expired. It might have been
      // updated already since it has been added to the processing queue.
      if (!$this->fieldNeedsRefresh($item)) {
        continue;
      }

      // Catch the case where the URL is not available. This can currently
      // happen only when an entity is malformed, for which we already check,
      // or the distribution we are attempting to track does not have a file.
      if (empty($this->getUrlParameter($entity))) {
        $message = $this->t('No URL parameter was found for the :bundle entity with id: @id', [
          ':bundle' => $entity->bundle(),
          '@id' => $entity->id(),
        ]);
        $this->loggerFactory->get('joinup_core')->error($message);
        continue;
      }

      $valid_items[] = $item;
    }

    return new ExpiredItemCollection($valid_items);
  }

  /**
   * Builds a set of subqueries to send to Matomo.
   *
   * @param \Drupal\cached_computed_field\ExpiredItemCollection $items
   *   A collection of items.
   *
   * @return \Matomo\ReportingApi\QueryInterface
   *   A Matomo reporting query object.
   */
  protected function buildQuery(ExpiredItemCollection $items): QueryInterface {
    // All requests are sent by POST method to handle the amount of concurrent
    // requests in terms of request length.
    $this->matomoQueryFactory->getQueryFactory()->getHttpClient()->setMethod('POST');
    $query = $this->matomoQueryFactory->getQuery('API.getBulkRequest');

    $url_index = 0;
    foreach ($items as $item) {
      $entity = $this->getEntity($item);
      $parameters = $this->getSubQueryParameters($entity);
      $query->setParameter('urls[' . $url_index++ . ']', http_build_query($parameters));
    }

    return $query;
  }

  /**
   * Returns the URL of the first file that is referenced in the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to retrieve the URL.
   *
   * @return string
   *   The URL of the first referenced file, or FALSE if the first file does not
   *   have a URL.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the functionality has not yet been implemented.
   */
  protected function getFileUrl(EntityInterface $entity): ?string {
    // We only support download counts for distribution entities at the moment.
    // @todo Make this more generic using the EntityFieldManager if we support
    //   more entity types in the future.
    // @todo Make this support multivalue fields if the need arises for this in
    //   the future.
    if ($entity->bundle() !== 'asset_distribution') {
      throw new \InvalidArgumentException('Retrieving files from entities other than distributions has not been implemented yet.');
    }

    /** @var \Drupal\file\FileInterface $file */
    foreach ($entity->field_ad_access_url->referencedEntities() as $file) {
      if ($file !== NULL) {
        return Url::fromUri(file_create_url($file->getFileUri()))
          ->setAbsolute()
          ->toString();
      }
    }

    return NULL;
  }

  /**
   * Returns the configuration for the given bundle.
   *
   * This can be configured by moderators in the 'Matomo Integration' settings
   * form.
   *
   * @param string $bundle
   *   The bundle for which to retrieve the settings.
   *
   * @return array
   *   The configuration settings array of the bundle.
   */
  protected function getMatomoSettings(string $bundle): array {
    if (empty($this->matomoSettings)) {
      $this->matomoSettings = $this->configFactory->get('joinup_core.matomo_settings');
    }
    foreach (['visit_counts', 'download_counts'] as $method) {
      $settings = $this->matomoSettings->get($method);
      if (array_key_exists($bundle, $settings)) {
        $settings[$bundle]['method'] = $method;
        return $settings[$bundle];
      }
    }

    return [];
  }

  /**
   * Builds a list of parameters for a sub query.
   *
   * This method gathers all information needed for each sub query separately.
   * All the default parameters, e.g. the authentication token and the site id,
   * are set automatically by generating a new query each time.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity with the expired field.
   * @param array $parameters
   *   A list of extra parameters to pass to the array of parameters.
   *
   * @return array
   *   A list of parameters or false if the entity does not have a valid url to
   *   request.
   */
  protected function getSubQueryParameters(EntityInterface $entity, array $parameters = []): array {
    $bundle = $entity->bundle();
    $period = $this->getTimePeriod($bundle);
    $type = $this->getType($bundle);
    $method = $this->getMatomoMethod($bundle);
    $url_parameter_name = $this->getUrlParameterName($bundle);
    $url_parameter = $this->getUrlParameter($entity);

    $sub_query = $this->matomoQueryFactory->getQuery($method);
    $date_range = $this->getDateRange($period);

    $parameters['period'] = 'range';
    $parameters['date'] = implode(',', $date_range);
    $parameters['method'] = $method;
    $parameters['showColumns'] = $type;
    $parameters[$url_parameter_name] = !empty($url_parameter) ? $url_parameter : '';
    // Default settings for current requests.
    $parameters['format'] = 'json';
    $parameters['module'] = 'API';
    // We are setting and retrieving the parameters in order to also get the
    // default parameters that the query comes with.
    $sub_query->setParameters($parameters);
    return $sub_query->getParameters();
  }

  /**
   * {@inheritdoc}
   */
  public function updateFieldValue(ExpiredItemInterface $expiredItem, $value) {
    $request_time = $this->time->getRequestTime();
    $cache_lifetime = $this->getField($expiredItem)->getSettings()['cache-max-age'];

    $entity = $this->getEntity($expiredItem);
    $entity->set($expiredItem->getFieldName(), [
      'value' => $value,
      'expire' => $request_time + $cache_lifetime,
    ]);
    // Set the flag to skip notifications for updates performed by cron.
    $entity->skip_notification = TRUE;
    $entity->save();
  }

}
