<?php

namespace Drupal\joinup_sparql\EventSubscriber;

use Drupal\sparql_entity_storage\Event\DefaultGraphsEvent;
use Drupal\sparql_entity_storage\Event\SparqlEntityStorageEvents;
use Drupal\sparql_entity_storage\SparqlGraphInterface;
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
      SparqlEntityStorageEvents::DEFAULT_GRAPHS => 'limitGraphs',
    ];
  }

  /**
   * Reacts to default graph list building event.
   *
   * @param \Drupal\sparql_entity_storage\Event\DefaultGraphsEvent $event
   *   The event.
   */
  public function limitGraphs(DefaultGraphsEvent $event) {
    $event->setDefaultGraphIds([SparqlGraphInterface::DEFAULT, 'draft']);
  }

}
