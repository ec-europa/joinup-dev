<?php

declare(strict_types = 1);

namespace Drupal\rdf_taxonomy\EventSubscriber;

use Drupal\sparql_entity_storage\Event\OutboundValueEvent;
use Drupal\sparql_entity_storage\Event\SparqlEntityStorageEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Massages outbound term parent value.
 */
class OutboundTermParentSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      SparqlEntityStorageEvents::OUTBOUND_VALUE => 'fixParentTermId',
    ];
  }

  /**
   * Fixes the term parent ID.
   *
   * Drupal core uses taxonomy terms with numeric IDs. If case, we convert the
   * term ID, from a numeric type to string.
   *
   * @param \Drupal\sparql_entity_storage\Event\OutboundValueEvent $event
   *   The outbound value event.
   */
  public function fixParentTermId(OutboundValueEvent $event) {
    if ($event->getEntityTypeId() === 'taxonomy_term' && $event->getField() === 'parent') {
      $event->setValue((string) $event->getValue());
    }
  }

}
