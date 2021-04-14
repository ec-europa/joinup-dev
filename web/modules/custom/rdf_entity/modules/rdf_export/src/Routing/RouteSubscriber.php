<?php

declare(strict_types = 1);

namespace Drupal\rdf_export\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for rdf export routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

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
      if ($route = $this->getRdfExportRoute($entity_type)) {
        $collection->add("entity.$entity_type_id.rdf_export", $route);
        $collection->add("entity.$entity_type_id.rdf_export_download", $this->getDownloadRoute($entity_type));
      }
    }
  }

  /**
   * Gets the devel load route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRdfExportRoute(EntityTypeInterface $entity_type) {
    if ($rdf_export = $entity_type->getLinkTemplate('rdf-export')) {
      $entity_type_id = $entity_type->id();

      $route = new Route($rdf_export);
      $route
        ->addDefaults([
          '_controller' => '\Drupal\rdf_export\Controller\RdfExportController::downloadLinks',
          '_title' => 'Export RDF Metadata',
        ])
        ->addRequirements([
          '_permission' => 'export rdf metadata',
        ])
        ->setOption('entity_type_id', $entity_type_id)
        ->setOption('parameters', [
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        ]);

      return $route;
    }
  }

  /**
   * Build the route for the actual download path.
   */
  protected function getDownloadRoute(EntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();

    $route = new Route("/rdf-export/$entity_type_id/{{$entity_type_id}}/{export_format}");
    $route
      ->addDefaults([
        '_controller' => '\Drupal\rdf_export\Controller\RdfExportController::download',
        '_title' => 'RDF Export',
      ])
      ->addRequirements([
        '_permission' => 'export rdf metadata',
      ])
      ->setOption('entity_type_id', $entity_type_id)
      ->setOption('parameters', [
        $entity_type_id => ['type' => 'entity:' . $entity_type_id],
      ]);

    return $route;
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
