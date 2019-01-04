<?php

namespace Drupal\joinup_sparql\EventSubscriber;

use Drupal\rdf_entity\Event\DefaultGraphsEvent;
use Drupal\rdf_entity\Event\RdfEntityEvents;
use Drupal\rdf_entity\RdfEntityGraphInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Alters the list of default graphs.
 */
class JoinupSparqlDefaultGraphsSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      RdfEntityEvents::DEFAULT_GRAPHS => 'limitGraphs',
    ];
  }

  /**
   * Reacts to default graph list building event.
   *
   * @param \Drupal\rdf_entity\Event\DefaultGraphsEvent $event
   *   The event.
   */
  public function limitGraphs(DefaultGraphsEvent $event) {
    $event->setDefaultGraphIds([RdfEntityGraphInterface::DEFAULT, 'draft']);
  }

}
