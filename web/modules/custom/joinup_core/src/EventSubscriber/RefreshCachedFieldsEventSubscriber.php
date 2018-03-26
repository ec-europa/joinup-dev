<?php

namespace Drupal\joinup_core\EventSubscriber;

use Drupal\cached_computed_field\Event\RefreshExpiredFieldsEventInterface;
use Drupal\cached_computed_field\EventSubscriber\RefreshExpiredFieldsSubscriberBase;
use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Url;
use Drupal\joinup_core\Exception\JoinupCoreUndefinedUrlException;
use Drupal\piwik_reporting_api\PiwikQueryFactoryInterface;

/**
 * Event subscriber that updates stale data with fresh results from Piwik.
 */
class RefreshCachedFieldsEventSubscriber extends RefreshExpiredFieldsSubscriberBase {

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
   * The Piwik query factory.
   *
   * @var \Drupal\piwik_reporting_api\PiwikQueryFactoryInterface
   */
  protected $piwikQueryFactory;

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
   * The Piwik settings config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $piwikSettings;

  /**
   * Constructs a new RefreshCachedPiwikDataEventSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The system time service.
   * @param \Drupal\piwik_reporting_api\PiwikQueryFactoryInterface $piwikQueryFactory
   *   The Piwik query factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, TimeInterface $time, PiwikQueryFactoryInterface $piwikQueryFactory, ConfigFactoryInterface $configFactory, LoggerChannelFactoryInterface $loggerFactory) {
    parent::__construct($entityTypeManager, $time);
    $this->piwikQueryFactory = $piwikQueryFactory;
    $this->configFactory = $configFactory;
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function refreshExpiredFields(RefreshExpiredFieldsEventInterface $event) {
    $items = $event->getExpiredItems()->getItems();

    // All requests are sent by POST method to handle the amount of concurrent
    // requests in terms of request length.
    $this->piwikQueryFactory->getQueryFactory()->getHttpClient()->setMethod('POST');
    $query = $this->piwikQueryFactory->getQuery('API.getBulkRequest');

    foreach ($items as $index => $item) {
      if (!$entity = $this->getEntity($item)) {
        continue;
      }

      // Only refresh the field if it has actually expired. It might have been
      // updated already since it has been added to the processing queue.
      if (!$this->fieldNeedsRefresh($item)) {
        continue;
      }

      $query->setParameter('urls[' . $index . ']', http_build_query($this->getSubQueryParameters($entity)));
    }

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
      foreach ($response_item as $year => $result) {
        $item = $result[0];
        if (!empty($item->$type)) {
          $count = $count + (int) $item->$type;
        }
      }

      $this->updateFieldValue($expired_item, $count);
    }
  }

  /**
   * Gets the correct URL parameter for the query.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity from which to prune the URL.
   *
   * @return string
   *   The url for the request.
   *
   * @throws \Drupal\joinup_core\Exception\JoinupCoreUndefinedUrlException
   *   Thrown when a URL could not be distilled from the given entity.
   */
  protected function getUrlParameter(ContentEntityInterface $entity) {
    $bundle = $entity->bundle();
    switch ($this->getMethod($bundle)) {
      case 'download_counts':
        $url = $this->getFileUrl($entity);
        break;

      default:
        $url = $this->getEntityUrl($entity);
        break;
    }

    if (empty($url)) {
      throw new JoinupCoreUndefinedUrlException();
    }

    return $url;
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
  protected function getTimePeriod($bundle) {
    $settings = $this->getPiwikSettings($bundle);
    if (!empty($settings['period'])) {
      return $settings['period'];
    }
    return 0;
  }

  /**
   * Returns the action type that is configured for the given bundle.
   *
   * @param string $bundle
   *   The bundle for which to retrieve the action type.
   *
   * @return string
   *   The action type to retrieve, as a parameter to be used in a Piwik API
   *   call. Defaults to 'nb_hits'.
   */
  protected function getType($bundle) {
    $settings = $this->getPiwikSettings($bundle);
    if (!empty($settings['type'])) {
      return $settings['type'];
    }
    return 'nb_hits';
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
  protected function getMethod($bundle) {
    $settings = $this->getPiwikSettings($bundle);
    return !empty($settings['method']) ? $settings['method'] : 'visit_counts';
  }

  /**
   * Returns the Piwik API method that applies to the given bundle.
   *
   * @param string $bundle
   *   The bundle for which to retrieve the API method.
   *
   * @return string
   *   The Piwik API method. Defaults to 'Actions.getPageUrl'.
   */
  protected function getPiwikMethod($bundle) {
    $method = $this->getMethod($bundle);
    return $method === 'download_counts' ? 'Actions.getDownload' : 'Actions.getPageUrl';
  }

  /**
   * Returns the Piwik URL parameter name that applies to the given bundle.
   *
   * @param string $bundle
   *   The bundle for which to retrieve the URL parameter name.
   *
   * @return string
   *   The URL parameter name. Defaults to 'pageUrl'.
   */
  protected function getUrlParameterName($bundle) {
    $method = $this->getMethod($bundle);
    return $method === 'download_counts' ? 'downloadUrl' : 'pageUrl';
  }

  /**
   * Returns the absolute URL to the canonical page of the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity for which to return the URL.
   *
   * @return string
   *   The URL.
   */
  protected function getEntityUrl(ContentEntityInterface $entity) {
    return $entity->toUrl()->setAbsolute()->toString();
  }

  /**
   * Returns the URL of the first file that is referenced in the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity for which to retrieve the URL.
   *
   * @return string|false
   *   The URL of the first referenced file, or FALSE if the first file does not
   *   have a URL.
   *
   * @throws \Exception
   *   Thrown when the functionality has not yet been implemented.
   */
  protected function getFileUrl(ContentEntityInterface $entity) {
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

    return FALSE;
  }

  /**
   * Returns the configuration for the given bundle.
   *
   * This can be configured by moderators in the 'Piwik Integration' settings
   * form.
   *
   * @param string $bundle
   *   The bundle for which to retrieve the settings.
   *
   * @return array|false
   *   The configuration array, or FALSE if there is no configuration for this
   *   bundle.
   */
  protected function getPiwikSettings($bundle) {
    if (empty($this->piwikSettings)) {
      $this->piwikSettings = $this->configFactory->get('joinup_core.piwik_settings');
    }
    foreach (['visit_counts', 'download_counts'] as $method) {
      $settings = $this->piwikSettings->get($method);
      if (array_key_exists($bundle, $settings)) {
        $settings[$bundle]['method'] = $method;
        return $settings[$bundle];
      }
    }

    return FALSE;
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
   *   A list of parameters.
   */
  protected function getSubQueryParameters(EntityInterface $entity, array $parameters = []): array {
    $bundle = $entity->bundle();
    $period = $this->getTimePeriod($bundle);
    $type = $this->getType($bundle);
    $method = $this->getPiwikMethod($bundle);
    $url_parameter_name = $this->getUrlParameterName($bundle);
    $url_parameter = $this->getUrlParameter($entity);

    $sub_query = $this->piwikQueryFactory->getQuery($method);
    $date_range = [
      // If the period is 0 we should get all results since launch.
      $period > 0 ? (new DateTimePlus("$period days ago"))->format('Y-m-d') : $this->configFactory->get('joinup_core.piwik_settings')->get('launch_date'),
      (new DateTimePlus())->format('Y-m-d'),
    ];

    $parameters['period'] = 'year';
    $parameters['date'] = implode(',', $date_range);
    $parameters['method'] = $method;
    $parameters['showColumns'] = $type;
    $parameters[$url_parameter_name] = $url_parameter;
    // Default settings for current requests.
    $parameters['format'] = 'json';
    $parameters['module'] = 'API';
    // We are setting and retrieving the parameters in order to also get the
    // default parameters that the query comes with.
    $sub_query->setParameters($parameters);
    return $sub_query->getParameters();
  }

}
