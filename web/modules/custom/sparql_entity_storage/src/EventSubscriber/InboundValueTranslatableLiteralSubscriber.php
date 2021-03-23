<?php

namespace Drupal\sparql_entity_storage\EventSubscriber;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\PrimitiveInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\sparql_entity_storage\Event\InboundValueEvent;
use Drupal\sparql_entity_storage\Event\SparqlEntityStorageEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Massages inbound translatable literal values.
 */
class InboundValueTranslatableLiteralSubscriber implements EventSubscriberInterface {

  /**
   * The typed-data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  /**
   * Constructs a new event subscriber.
   *
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typed_data_manager
   *   The typed-data manager.
   */
  public function __construct(TypedDataManagerInterface $typed_data_manager) {
    $this->typedDataManager = $typed_data_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      SparqlEntityStorageEvents::INBOUND_VALUE => 'castTranslatableLiteralValue',
    ];
  }

  /**
   * Massages inbound translatable literal value value.
   *
   * @param \Drupal\sparql_entity_storage\Event\InboundValueEvent $event
   *   The inbound value event.
   */
  public function castTranslatableLiteralValue(InboundValueEvent $event) {
    $mapping_info = $event->getFieldMappingInfo();

    // There's no way to store translated values other than with `t_literal' but
    // in Drupal we might have other types of primitives that are translatable,
    // like translated integers, booleans, etc. We cast the values here to get
    // the correct primitive type.
    if ($mapping_info['format'] === 't_literal' && $mapping_info['data_type'] !== 'string') {
      $typed_data_plugin = $this->typedDataManager->create(DataDefinition::createFromDataType($mapping_info['data_type']));
      if ($typed_data_plugin instanceof PrimitiveInterface) {
        $typed_data_plugin->setValue($event->getValue());
        $event->setValue($typed_data_plugin->getCastedValue());
      }
    }
  }

}
