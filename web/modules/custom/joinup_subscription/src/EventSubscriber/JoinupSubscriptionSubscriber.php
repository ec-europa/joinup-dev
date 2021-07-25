<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription\EventSubscriber;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\joinup_group\Event\GroupReport;
use Drupal\joinup_group\Event\GroupReportsEventInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Various event subscribers.
 *
 * This contains event subscribers for the joinup_subscription module which do
 * not require complex logic.
 */
class JoinupSubscriptionSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      GroupReportsEventInterface::EVENT_NAME => ['onGroupReportsCommunity'],
    ];
  }

  /**
   * Subscriber for the event that collects group reports.
   *
   * Provides the subscribers report.
   *
   * @param \Drupal\joinup_group\Event\GroupReportsEventInterface $event
   *   The group reports event.
   *
   * @see \Drupal\joinup_subscription\Controller\SubscribersReportController
   */
  public function onGroupReportsCommunity(GroupReportsEventInterface $event) {
    $event->addGroupReport(new GroupReport(
      'subscribers',
      $this->t('Subscribers report'),
      Url::fromRoute('joinup_subscription.group_subscribers_report', [
        'rdf_entity' => $event->getGroup()->id(),
      ]),
      NULL
    ));
  }

}
