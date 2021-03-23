<?php

namespace Drupal\sparql_graph_test;

use Drupal\sparql_entity_storage\Event\DefaultGraphsEvent;
use Drupal\sparql_entity_storage\Event\SparqlEntityStorageEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Alter the list of default graphs.
 */
class DefaultGraphsSubscriber implements EventSubscriberInterface {

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
    $graphs = $event->getDefaultGraphIds();
    if (($index = array_search('non_default_graph', $graphs)) !== FALSE) {
      // Remove 'non_default_graph' graph.
      unset($graphs[$index]);
    }
    // Add a disabled graph.
    $graphs[] = 'disabled_graph';
    // Add an non-existing graph.
    $graphs[] = 'non_existing_graph';

    $event->setDefaultGraphIds($graphs);
  }

}
