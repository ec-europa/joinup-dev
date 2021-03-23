<?php

namespace Drupal\sparql_entity_storage\EventSubscriber;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\sparql_entity_storage\Event\OutboundValueEvent;
use Drupal\sparql_entity_storage\Event\SparqlEntityStorageEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Massages outbound date/time values.
 */
class OutboundValueDateTimeSubscriber implements EventSubscriberInterface {

  use DateTimeTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      SparqlEntityStorageEvents::OUTBOUND_VALUE => 'massageOutboundValue',
    ];
  }

  /**
   * Massages outbound values.
   *
   * Converts field properties with a "timestamp" data type that have been
   * mapped to date formats (xsd:date or xsd:dateTime).
   *
   * @param \Drupal\sparql_entity_storage\Event\OutboundValueEvent $event
   *   The outbound value event.
   */
  public function massageOutboundValue(OutboundValueEvent $event) {
    $mapping_info = $event->getFieldMappingInfo();

    if ($this->isTimestampAsDateField($mapping_info)) {
      $value = DrupalDateTime::createFromTimestamp($event->getValue());
      $event->setValue($value->format($this->getDateDataTypes()[$mapping_info['format']]));
    }
  }

}
