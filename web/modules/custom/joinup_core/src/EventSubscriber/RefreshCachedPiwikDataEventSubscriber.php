<?php

namespace Drupal\joinup_core\EventSubscriber;

use Drupal\cached_computed_field\Event\RefreshExpiredFieldEventInterface;
use Drupal\cached_computed_field\EventSubscriber\RefreshExpiredFieldSubscriberBase;
use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, TimeInterface $time, PiwikQueryFactoryInterface $piwikQueryFactory, ConfigFactoryInterface $configFactory) {
    parent::__construct($entityTypeManager, $time);
    $this->piwikQueryFactory = $piwikQueryFactory;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function refreshExpiredField(RefreshExpiredFieldEventInterface $event) {
    // Only react to fields that store the download count or visit count.
    $field_name = $event->getFieldName();
    if (!in_array($field_name, [static::FIELD_NAME_DOWNLOAD_COUNT, static::FIELD_NAME_VISIT_COUNT])) {
      return;
    }
    // Only refresh the field if it has actually expired. It might have been
    // updated already since it has been added to the processing queue.
    if (!$this->fieldNeedsRefresh($event)) {
      return;
    }

    $new_value = $this->getCount($this->getEntity($event), $field_name);
    if ($new_value !== FALSE) {
      $this->updateFieldValue($event, $new_value);
    }
  }

  /**
   * Returns the action count the given entity received in the given period.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check.
   * @param string $field_name
   *   The name of the field that stores the value.
   *
   * @return int|false
   *   The number of visits, or FALSE if the number of visits could not be
   *   determined.
   */
  protected function getCount(ContentEntityInterface $entity, $field_name) {
    $bundle = $entity->bundle();
    $period = $this->getTimePeriod($bundle);
    $type = $this->getType($bundle);
    $method = $this->getPiwikMethod($field_name);

    $date_range = [
      (new DateTimePlus("$period days ago"))->format('Y-m-d'),
      (new DateTimePlus())->format('Y-m-d'),
    ];

    $query = $this->piwikQueryFactory->getQuery($method);
    $this->setUrlParameter($query, $entity);
    $query->setParameters([
      'pageUrl' => $entity->toUrl()->setAbsolute()->toString(),
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
   */
  protected function setUrlParameter(QueryInterface $query, ContentEntityInterface $entity) {
    $bundle = $entity->bundle();
    switch ($this->getMethod($bundle)) {
      case 'download_counts':
        $query->setParameter($this->getUrlParameterName($bundle), $this->getFileUrl($entity));
        break;

      case 'visit_counts':
        $query->setParameter($this->getUrlParameterName($bundle), $this->getEntityUrl($entity));
        break;
    }
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
   * @throws \Exception
   *   Thrown when the functionality has not yet been implemented.
   */
  protected function getFileUrl(ContentEntityInterface $entity) {
    // We only support download counts for distribution entities at the moment.
    // @todo Make this more generic using the EntityFieldManager if we support
    //   more entity types in the future.
    if ($entity->bundle() !== 'asset_distribution') {
      throw new \InvalidArgumentException('Retrieving files from entities other than distributions has not been implemented yet.');
    }

    // @todo ref. TrackedHostedFileDownloadFormatter::viewElements()
    throw new \Exception('todo');
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
        $settings['method'] = $method;
        return $settings[$bundle];
      }
    }

    return FALSE;
  }

}
