<?php

namespace Drupal\joinup_core\EventSubscriber;

use Drupal\cached_computed_field\Event\RefreshExpiredFieldEventInterface;
use Drupal\cached_computed_field\EventSubscriber\RefreshExpiredFieldSubscriberBase;
use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\piwik_reporting_api\PiwikQueryFactoryInterface;

/**
 * Event subscriber that updates the visit count with fresh data from Piwik.
 */
class RefreshVisitCountEventSubscriber extends RefreshExpiredFieldSubscriberBase {

  /**
   * The name of the field that contains the visit count.
   *
   * @var string
   */
  const FIELD_NAME = 'field_visit_count';

  /**
   * The period for which to check the visit count, in days.
   *
   * This is equivalent to 6 standard earth months.
   *
   * @todo Store in config and provide a settings form.
   *
   * @var int
   */
  const PERIOD = 183;

  /**
   * The Piwik query factory.
   *
   * @var \Drupal\piwik_reporting_api\PiwikQueryFactoryInterface
   */
  protected $piwikQueryFactory;

  /**
   * Constructs a new RefreshVisitCountEventSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The system time service.
   * @param \Drupal\piwik_reporting_api\PiwikQueryFactoryInterface $piwikQueryFactory
   *   The Piwik query factory.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, TimeInterface $time, PiwikQueryFactoryInterface $piwikQueryFactory) {
    parent::__construct($entityTypeManager, $time);
    $this->piwikQueryFactory = $piwikQueryFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function refreshExpiredField(RefreshExpiredFieldEventInterface $event) {
    // Only react to fields that store the visit count.
    if ($event->getFieldName() !== static::FIELD_NAME) {
      return;
    }
    // Only refresh the field if it has actually expired. It might have been
    // updated already since it has been added to the processing queue.
    if (!$this->fieldNeedsRefresh($event)) {
      return;
    }

    $entity = $this->getEntity($event);
    $visit_count = $this->getVisitCount($entity, static::PERIOD);

    if ($visit_count !== FALSE) {
      $this->updateFieldValue($event, $visit_count);
    }
  }

  /**
   * Returns the number of visits the given entity received in the given period.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check.
   * @param int $period
   *   The period for which to check the visit, in days.
   *
   * @return int|false
   *   The number of visits, or FALSE if the number of visits could not be
   *   determined.
   */
  protected function getVisitCount(ContentEntityInterface $entity, int $period) {
    $date_range = [
      (new DateTimePlus("$period days ago"))->format('Y-m-d'),
      (new DateTimePlus())->format('Y-m-d'),
    ];
    $query = $this->piwikQueryFactory->getQuery('Actions.getPageUrl');
    $query->setParameters([
      'pageUrl' => $entity->toUrl()->setAbsolute()->toString(),
      'period' => 'range',
      'date' => implode(',', $date_range),
      'showColumns' => 'nb_visits',
    ]);
    $response = $query->execute();
    if (!$response->hasError()) {
      $result = reset($response->getResponse());
      if (!empty($result->nb_visits)) {
        return $result->nb_visits;
      }
      // No error occurred, but no visits have been recorded in Piwik.
      return 0;
    }
    return FALSE;
  }

}
