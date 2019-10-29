<?php

declare(strict_types = 1);

namespace Drupal\joinup_stats\Event\Subscriber;

use Drupal\cached_computed_field\Event\RefreshExpiredFieldsEventInterface;
use Drupal\cached_computed_field\EventSubscriber\RefreshExpiredFieldsSubscriberBase;
use Drupal\cached_computed_field\ExpiredItemCollection;
use Drupal\cached_computed_field\ExpiredItemInterface;
use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Url;
use Drupal\file_url\Entity\RemoteFile;
use Drupal\matomo_reporting_api\MatomoQueryFactoryInterface;
use Drupal\meta_entity\Entity\MetaEntityInterface;
use Drupal\meta_entity\Entity\MetaEntityType;
use Matomo\ReportingApi\QueryInterface;

/**
 * Event subscriber that updates counters with data drawn from Matomo.
 *
 * Data currently include the download count for distributions and visit count
 * for nodes.
 */
class RefreshCachedFieldsEventSubscriber extends RefreshExpiredFieldsSubscriberBase {

  /**
   * The Matomo query factory.
   *
   * @var \Drupal\matomo_reporting_api\MatomoQueryFactoryInterface
   */
  protected $matomoQueryFactory;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Settings derived from meta entity types data.
   *
   * @var array
   */
  protected $metaEntityTypeSettings;

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
      $this->loggerFactory->get('joinup_stats')->error($e->getMessage());
      return;
    }

    $errors = [];
    foreach ($items as $index => $expired_item) {
      $response_item = $response[$index];
      // If an error occurs, the response for the expired item is an object
      // rather than an array of objects.
      if (is_object($response_item) && isset($response_item->result) && $response_item->result === 'error') {
        $message = $response_item->message ?? '[unknown]';
        $errors[$message][] = $expired_item->getEntityId();
        continue;
      }
      /** @var \Drupal\meta_entity\Entity\MetaEntityInterface $meta_entity */
      $meta_entity = $this->getEntity($expired_item);
      $type = $this->getSettingsForMetaEntity($meta_entity)['type'];
      $count = 0;
      foreach ($response_item as $result) {
        if (!empty($result->$type)) {
          $count = $count + (int) $result->$type;
        }
      }

      $this->updateFieldValue($expired_item, $count);
    }

    if (!empty($errors)) {
      $stats_channel = $this->loggerFactory->get('joinup_stats');
      foreach ($errors as $message => $ids) {
        sort($ids, SORT_NUMERIC);
        $stats_channel->error("Matomo error '@error' on meta entities: @ids.", [
          '@error' => $message,
          '@ids' => implode(', ', $ids),
        ]);
      }
    }
  }

  /**
   * Gets the URL parameter for the query.
   *
   * @param \Drupal\meta_entity\Entity\MetaEntityInterface $meta_entity
   *   The entity that the request URL is related to.
   *
   * @return string
   *   The URL for the request or an empty string if no URL is detected.
   *
   * @throws \Exception
   *   On malformed settings.
   */
  protected function getUrlParameter(MetaEntityInterface $meta_entity): string {
    $method = $this->getSettingsForMetaEntity($meta_entity)['parameter_method'];
    return $this->$method($meta_entity);
  }

  /**
   * Returns the absolute URL to the canonical page of the given entity.
   *
   * @param \Drupal\meta_entity\Entity\MetaEntityInterface $meta_entity
   *   The entity for which to return the URL.
   *
   * @return string
   *   The entity URL.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   *   In case of malformed entity.
   */
  protected function getEntityUrl(MetaEntityInterface $meta_entity): string {
    return $meta_entity->getTargetEntity()->toUrl()->setAbsolute()->toString();
  }

  /**
   * Returns the URL of the file attached to the target distribution.
   *
   * @param \Drupal\meta_entity\Entity\MetaEntityInterface $meta_entity
   *   The meta entity for which to retrieve the URL.
   *
   * @return string
   *   The URL of the distribution file, or an empty string if no URL.
   *
   * @throws \InvalidArgumentException
   *   Thrown when target entity is not a distribution.
   */
  protected function getDistributionFileUrl(MetaEntityInterface $meta_entity): string {
    /** @var \Drupal\rdf_entity\RdfInterface $distribution */
    $distribution = $meta_entity->getTargetEntity();
    if ($distribution->bundle() !== 'asset_distribution') {
      throw new \InvalidArgumentException('Retrieving files from entities other than distributions has not been implemented yet.');
    }

    /** @var \Drupal\file\FileInterface $file */
    foreach ($distribution->field_ad_access_url->referencedEntities() as $file) {
      if ($file !== NULL) {
        return ($file instanceof RemoteFile) ?
          $file->getFileUri() :
          Url::fromUri(file_create_url($file->getFileUri()))->setAbsolute()->toString();
      }
    }

    // For distributions without attached files.
    return '';
  }

  /**
   * Generates an argument array that represents the time range.
   *
   * @param int $period
   *   The starting period represented by the number of days ago.
   *
   * @return string[]
   *   An array with two values: the start date and the end date (which will
   *   always be the current date).
   */
  protected function getDateRange(int $period): array {
    $launch_date = $this->configFactory->get('joinup_stats.settings')->get('launch_date');
    return [
      // If the period is 0 we should get all results since launch.
      $period > 0 ? (new DateTimePlus("$period days ago"))->format('Y-m-d') : $launch_date,
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
      /** @var \Drupal\meta_entity\Entity\MetaEntityInterface $meta_entity */
      if (!$meta_entity = $this->getEntity($item)) {
        continue;
      }

      // Only refresh the field if it has actually expired. It might have been
      // updated already since it has been added to the processing queue.
      if (!$this->fieldNeedsRefresh($item)) {
        continue;
      }

      // Catch the case where the URL is not available. This can currently
      // happen only when a distribution we are attempting to track doesn't
      // have a file attached, but an external link.
      if (empty($this->getUrlParameter($meta_entity))) {
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
      /** @var \Drupal\meta_entity\Entity\MetaEntityInterface $meta_entity */
      $meta_entity = $this->getEntity($item);
      $parameters = $this->getSubQueryParameters($meta_entity);
      $query->setParameter('urls[' . $url_index++ . ']', http_build_query($parameters));
    }

    return $query;
  }

  /**
   * Builds a list of parameters for a sub query.
   *
   * This method gathers all information needed for each sub query separately.
   * All the default parameters, e.g. the authentication token and the site ID,
   * are set automatically by generating a new query each time.
   *
   * @param \Drupal\meta_entity\Entity\MetaEntityInterface $meta_entity
   *   The entity with the expired field.
   * @param array $parameters
   *   A list of extra parameters to pass to the array of parameters.
   *
   * @return array
   *   A list of parameters or FALSE if the entity does not have a valid URL to
   *   request.
   *
   * @throws \Exception
   *   When the meta entity type configs are malformed.
   */
  protected function getSubQueryParameters(MetaEntityInterface $meta_entity, array $parameters = []): array {
    $stats_settings = $this->getSettingsForMetaEntity($meta_entity);
    $sub_query = $this->matomoQueryFactory->getQuery($stats_settings['matomo_method']);
    $date_range = $this->getDateRange($stats_settings['period']);

    $parameters['period'] = 'range';
    $parameters['date'] = implode(',', $date_range);
    $parameters['method'] = $stats_settings['matomo_method'];
    $parameters['showColumns'] = $stats_settings['type'];
    $parameters[$stats_settings['parameter_name']] = $this->getUrlParameter($meta_entity);
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

    $this->getEntity($expiredItem)->set($expiredItem->getFieldName(), [
      'value' => $value,
      'expire' => $request_time + $cache_lifetime,
    ])->save();
  }

  /**
   * Retrieves the statistics configurations for a given meta entity.
   *
   * @param \Drupal\meta_entity\Entity\MetaEntityInterface $meta_entity
   *   The meta entity to retrieve the stats settings for.
   *
   * @return array
   *   The stats settings.
   *
   * @throws \Exception
   *   When the configuration is incomplete or 'parameter_method' settings is
   *   not a callable.
   */
  protected function getSettingsForMetaEntity(MetaEntityInterface $meta_entity): array {
    if (!isset($this->metaEntityTypeSettings)) {
      $this->metaEntityTypeSettings = [];
      foreach (MetaEntityType::loadMultiple() as $type => $meta_entity_type) {
        $this->metaEntityTypeSettings[$type] = $meta_entity_type->getThirdPartySettings('joinup_stats');

        // Ensure configuration integrity.
        $mandatory_settings = [
          'matomo_method',
          'parameter_name',
          'parameter_method',
          'period',
          'type',
        ];
        foreach ($mandatory_settings as $key) {
          if (!array_key_exists($key, $this->metaEntityTypeSettings[$type])) {
            throw new \Exception("Incomplete configuration for '$type' meta entity type: missing {$key} key.");
          }
        }
        $parameter_method_whitelist = ['getDistributionFileUrl', 'getEntityUrl'];
        $parameter_method = $this->metaEntityTypeSettings[$type]['parameter_method'];
        if (!is_callable([$this, $parameter_method]) || !in_array($parameter_method, $parameter_method_whitelist)) {
          throw new \Exception("::{$this->metaEntityTypeSettings['parameter_method']}() is not a valid parameter method.");
        }
      }
    }
    return $this->metaEntityTypeSettings[$meta_entity->bundle()];
  }

}
