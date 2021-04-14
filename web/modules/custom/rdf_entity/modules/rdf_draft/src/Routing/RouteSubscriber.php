<?php

declare(strict_types = 1);

namespace Drupal\rdf_draft\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\sparql_entity_storage\SparqlEntityStorage;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for rdf export routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      if ($storage instanceof SparqlEntityStorage) {
        $definitions = $storage->getGraphDefinitions();
        // Default is already covered by the 'view' operation.
        unset($definitions['default']);
        foreach ($definitions as $name => $definition) {
          $definition['name'] = $name;
          if ($route = $this->getRdfGraphRoute($entity_type, $definition)) {
            $collection->add("entity.$entity_type_id.rdf_draft_$name", $route);
          }
        }
      }
    }
  }

  /**
   * Gets the devel load route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param array $graph_definition
   *   The graph definition.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRdfGraphRoute(EntityTypeInterface $entity_type, array $graph_definition) {
    if ($rdf_draft = $entity_type->getLinkTemplate('rdf-draft-' . $graph_definition['name'])) {
      $entity_type_id = $entity_type->id();

      $route = new Route($rdf_draft);
      $route
        ->addDefaults([
          '_controller' => '\Drupal\rdf_draft\Controller\RdfController::view',
          '_title' => (string) $this->t('View @title', ['@title' => (string) $graph_definition['title']]),
        ])
        ->addRequirements([
          '_access_rdf_graph' => 'TRUE',
        ])
        ->setOption('entity_type_id', $entity_type_id)
        ->setOption('graph_name', $graph_definition['name'])
        ->setOption('parameters', [
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        ]);

      return $route;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', 100];
    return $events;
  }

}
