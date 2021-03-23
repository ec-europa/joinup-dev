<?php

namespace Drupal\sparql_entity_storage\Event;

/**
 * Contains all event IDs.
 */
final class SparqlEntityStorageEvents {

  /**
   * The event triggered when determining the graph during parameter conversion.
   *
   * @Event
   *
   * @see \Drupal\sparql_entity_storage\ParamConverter\SparqlEntityStorageConverter::convert()
   *
   * @var string
   */
  const GRAPH_ENTITY_CONVERT = 'sparql.entity_convert';

  /**
   * The name of the event triggered when an inbound value is being processed.
   *
   * @Event
   *
   * @see \Drupal\sparql_entity_storage\SparqlEntityStorageFieldHandler::getInboundValue()
   *
   * @var string
   */
  const INBOUND_VALUE = 'sparql.inbound_value';

  /**
   * The name of the event triggered when an outbound value is being processed.
   *
   * @Event
   *
   * @see \Drupal\sparql_entity_storage\SparqlEntityStorageFieldHandler::getOutboundValue()
   *
   * @var string
   */
  const OUTBOUND_VALUE = 'sparql.outbound_value';

  /**
   * The name of the event triggered when building the list of default graphs.
   *
   * @Event
   *
   * @see \Drupal\sparql_entity_storage\SparqlEntityStorageGraphHandler::getEntityTypeDefaultGraphIds()
   *
   * @var string
   */
  const DEFAULT_GRAPHS = 'sparql.default_graphs';

}
