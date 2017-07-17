<?php

namespace Drupal\joinup_core\EventSubscriber;

use Drupal\cached_computed_field\Event\RefreshExpiredFieldEventInterface;
use Drupal\cached_computed_field\EventSubscriber\RefreshExpiredFieldSubscriberBase;
use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Url;
use Drupal\joinup_core\Exception\JoinupCoreUndefinedUrlException;
use Drupal\piwik_reporting_api\PiwikQueryFactoryInterface;
use Piwik\ReportingApi\QueryInterface;

/**
 * Event subscriber that updates stale data with fresh results from Piwik.
 */
class RefreshCachedPiwikDataEventSubscriber extends RefreshExpiredFieldSubscriberBase {

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
  public function refreshExpiredField(RefreshExpiredFieldEventInterface $event) {
    // Only react to fields that store the download count or visit count.
    if (!in_array($event->getFieldName(), [static::FIELD_NAME_DOWNLOAD_COUNT, static::FIELD_NAME_VISIT_COUNT])) {
      return;
    }
    // Only refresh the field if it has actually expired. It might have been
    // updated already since it has been added to the processing queue.
    if (!$this->fieldNeedsRefresh($event)) {
      return;
    }

    $new_value = $this->getCount($this->getEntity($event));
    if ($new_value !== FALSE) {
      $this->updateFieldValue($event, $new_value);
    }
  }

  /**
   * Returns the action count the given entity received in the given period.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check.
   *
   * @return int|false
   *   The number of visits, or FALSE if the number of visits could not be
   *   determined.
   */
  protected function getCount(ContentEntityInterface $entity) {
    $bundle = $entity->bundle();
    $period = $this->getTimePeriod($bundle);
    $type = $this->getType($bundle);
    $method = $this->getPiwikMethod($bundle);

    // Start building the query.
    $query = $this->piwikQueryFactory->getQuery($method);

    // Try to retrieve the URL from the entity.
    try {
      $this->setUrlParameter($query, $entity);
    }
    catch (JoinupCoreUndefinedUrlException $e) {
      // If no URL was found then we cannot get results from Piwik. This can
      // happen for example when retrieving the download count from a
      // distribution that has no files associated with it.
      // We are returning 0 instead of FALSE so that this field will not be
      // re-queued on every cron run for eternity.
      return 0;
    }

    $date_range = [
      // If the period is 0 we should get all results since launch.
      $period > 0 ? (new DateTimePlus("$period days ago"))->format('Y-m-d') : $this->configFactory->get('joinup_core.piwik_settings')->get('launch_date'),
      (new DateTimePlus())->format('Y-m-d'),
    ];

    $query->setParameters([
      'period' => 'range',
      'date' => implode(',', $date_range),
      'showColumns' => $type,
    ]);
    $response = $query->execute();
    if (!$response->hasError()) {
      $result = reset($response->getResponse());
      if (!empty($result->$type)) {
        return $result->$type;
      }
      // No error occurred, but no results have been recorded in Piwik.
      return 0;
    }

    // An error occurred. Log it to notify the devops team.
    $message = 'Error "@error" occurred when querying the Piwik API to get information on the entity of type "@entity_type_id" with ID "@entity_id".';
    $arguments = [
      '@error' => $response->getErrorMessage(),
      '@entity_type_id' => $entity->getEntityTypeId(),
      '@entity_id' => $entity->id(),
    ];
    $this->loggerFactory->get('joinup_core')->warning($message, $arguments);

    return FALSE;
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
   * Sets the correct URL parameter on the query.
   *
   * @param \Piwik\ReportingApi\QueryInterface $query
   *   The Piwik reporting API query object.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity from which to prune the URL.
   *
   * @throws \Drupal\joinup_core\Exception\JoinupCoreUndefinedUrlException
   *   Thrown when a URL could not be distilled from the given entity.
   */
  protected function setUrlParameter(QueryInterface $query, ContentEntityInterface $entity) {
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

    $query->setParameter($this->getUrlParameterName($bundle), $url);
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

}
