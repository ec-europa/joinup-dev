<?php

declare(strict_types = 1);

namespace Drupal\isa2_analytics\EventSubscriber;

use Drupal\oe_webtools_analytics\AnalyticsEventInterface;
use Drupal\og\OgContextInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\oe_webtools_analytics\Event\AnalyticsEvent;

/**
 * Subscriber that acts on visitor analytics being collected for reporting.
 */
class WebtoolsAnalyticsSubscriber implements EventSubscriberInterface {

  /**
   * The OG context service.
   *
   * @var \Drupal\og\OgContextInterface
   */
  protected $ogContext;

  /**
   * Constructs a new WebtoolsAnalyticsSubscriber.
   *
   * @param \Drupal\og\OgContextInterface $ogContext
   *   The OG context service.
   */
  public function __construct(OgContextInterface $ogContext) {
    $this->ogContext = $ogContext;
  }

  /**
   * Sets the site section on the analytics event.
   *
   * If the current page belongs to a certain group (collection or solution)
   * then this will be reported to Webtools Analytics as a "site section". This
   * allows the visitor data to be analysed for specific collections and
   * solutions.
   *
   * @param \Drupal\oe_webtools_analytics\AnalyticsEventInterface $event
   *   Response event.
   */
  public function setSiteSection(AnalyticsEventInterface $event) {
    // The site section varies by the active group.
    $event->addCacheContexts(['og_group_context']);

    // Set the group label as the site section if there is an active group.
    if ($group = $this->ogContext->getGroup()) {
      $event->setSiteSection($group->label());
      $event->addCacheableDependency($group);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AnalyticsEvent::NAME][] = ['setSiteSection'];

    return $events;
  }

}
