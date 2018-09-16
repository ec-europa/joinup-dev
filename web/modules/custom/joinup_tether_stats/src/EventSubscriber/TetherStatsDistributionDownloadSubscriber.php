<?php

namespace Drupal\joinup_tether_stats\EventSubscriber;

use Drupal\Component\Utility\UrlHelper;
use Drupal\rdf_entity\Entity\Rdf;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\tether_stats\TetherStatsEvents;
use Drupal\tether_stats\Event\TetherStatsRequestToElementEvent;
use Drupal\tether_stats\TetherStatsIdentitySet;

/**
 * A subscriber to track download links in distributions entities.
 *
 * @todo: The tracker should ignore requests made within a certain timeframe
 * (TBD) after the previous request.
 */
class TetherStatsDistributionDownloadSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {

    $events[TetherStatsEvents::REQUEST_TO_ELEMENT] = 'onRequestToElement';
    return $events;
  }

  /**
   * This handler will bind node pages to respective stat elements.
   *
   * @param \Drupal\tether_stats\Event\TetherStatsRequestToElementEvent $event
   *   The request to element event.
   */
  public function onRequestToElement(TetherStatsRequestToElementEvent $event) {
    $route_match = $event->getRouteMatch();
    // Only tracking of download links is handled here.
    if ($route_match->getRouteName() !== 'tether_stats.track') {
      return;
    }

    $request_uri = $event->getRequestUri();
    $parsed_uri = UrlHelper::parse($request_uri);

    if (empty($parsed_uri['query']['entity_type']) || $parsed_uri['query']['entity_type'] !== 'rdf_entity' || empty($parsed_uri['query']['entity_id'])) {
      return;
    }

    if (!($distribution = Rdf::load($parsed_uri['query']['entity_id']))) {
      return;
    }

    if ($distribution->bundle() !== 'asset_distribution') {
      return;
    }

    $identity_set = new TetherStatsIdentitySet([
      'url' => $distribution->toUrl()->toString(),
      'name' => 'download-track',
    ]);
    $event->setIdentitySet($identity_set);
  }

}
