<?php

declare(strict_types = 1);

namespace Drupal\rdf_draft\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\sparql_entity_storage\Event\ActiveGraphEvent;
use Drupal\sparql_entity_storage\Event\SparqlEntityStorageEvents;
use Drupal\sparql_entity_storage\SparqlEntityStorageGraphHandlerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Load the draft entity on the edit form and on the draft tab.
 */
class ActiveGraphSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The SPARQL graph handler service.
   *
   * @var \Drupal\sparql_entity_storage\SparqlEntityStorageGraphHandlerInterface
   */
  protected $sparqlGraphHandler;

  /**
   * Constructs a new event subscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\sparql_entity_storage\SparqlEntityStorageGraphHandlerInterface $sparql_graph_handler
   *   The SPARQL graph handler service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, SparqlEntityStorageGraphHandlerInterface $sparql_graph_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->sparqlGraphHandler = $sparql_graph_handler;
  }

  /**
   * Set the appropriate graph as an active graph for the entity.
   *
   * Currently, the following cases exist:
   * - In the canonical view of the entity, load the entity from all graphs. If
   *   a published one exists, then it uses the default behaviour. If a
   *   published one does not exist, then returns the draft version and
   *   continues with proper access check.
   * - In the edit view, the draft version has priority over the published. If a
   *   draft version exists, then this is the one edited. If a draft version
   *   does not exist, then the published one is cloned into the draft graph.
   * - The delete view is the same as the canonical view. The published one has
   *   priority over the draft version.
   * - In any other case, like a 'view draft' tab view, the corresponding graph
   *   is loaded with no fallbacks.
   *
   * @param \Drupal\sparql_entity_storage\Event\ActiveGraphEvent $event
   *   The event object to process.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the access is denied and redirects to user login page.
   */
  public function graphForEntityConvert(ActiveGraphEvent $event) {
    $defaults = $event->getRouteDefaults();
    if ($defaults['_route']) {
      $entity_type_id = $event->getEntityTypeId();
      $default_graph_id = $this->sparqlGraphHandler->getDefaultGraphId($entity_type_id);
      $entity_type_has_draft = in_array('draft', $this->sparqlGraphHandler->getEntityTypeGraphIds($entity_type_id));

      /** @var \Drupal\sparql_entity_storage\SparqlEntityStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      $route_parts = explode('.', $defaults['_route']);
      // On the edit form, load from draft graph, if possible.
      if (array_search('edit_form', $route_parts)) {
        $graph_ids = $entity_type_has_draft ? ['draft', $default_graph_id] : [
          $default_graph_id,
        ];
        $entity = $storage->load($event->getEntityId(), $graph_ids);

        // If the entity is empty, it means the user tried to access the edit
        // route of a non existing entity. In that case, simply return and let
        // the RDF entity try to load the entity from all graphs.
        if (empty($entity)) {
          return;
        }

        // Even an entity type supports a graph, it might have bundles that
        // are not providing support for that graph. In addition to the entity
        // type check, we need to check also the bundle.
        if ($entity_type_has_draft && $this->sparqlGraphHandler->bundleHasGraph($entity_type_id, $entity->bundle(), 'draft')) {
          $event->setGraphs(['draft', $default_graph_id]);
        }
        else {
          $event->setGraphs([$default_graph_id]);
        }
      }
      // Viewing the entity on a graph specific tab.
      elseif (isset($route_parts[2]) && (strpos($route_parts[2], 'rdf_draft_') === 0)) {
        // Retrieve the graph name from the route.
        $graph_id = str_replace('rdf_draft_', '', $route_parts[2]);
        $event->setGraphs([$graph_id]);
      }
      // On the canonical route, the default entity is preferred.
      elseif (isset($route_parts[2]) && $route_parts[2] === 'canonical') {
        $graph_ids = $entity_type_has_draft ? [$default_graph_id, 'draft'] : [
          $default_graph_id,
        ];
        $event->setGraphs($graph_ids);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      SparqlEntityStorageEvents::GRAPH_ENTITY_CONVERT => ['graphForEntityConvert'],
    ];
  }

}
