<?php

declare(strict_types = 1);

namespace Drupal\rdf_draft\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for rdf_draft.module.
 */
class RdfController extends ControllerBase {
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Creates an RdfController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * Build the view draft page.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return array
   *   Render array.
   *
   * @throws \Exception
   *   Thrown when the entity is not found in the given graph.
   */
  public function view(RouteMatchInterface $route_match) {
    $parameter_name = $route_match->getRouteObject()->getOption('entity_type_id');
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $route_match->getParameter($parameter_name);
    /** @var \Drupal\sparql_entity_storage\SparqlEntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    $graph_name = $route_match->getRouteObject()->getOption('graph_name');
    $draft_entity = $storage->load($entity->id(), [$graph_name]);
    if (!$draft_entity) {
      // Should not occur: RdfGraphAccessCheck validates that the entity exists.
      throw new \Exception('Entity not loaded from graph');
    }
    $page = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId())->view($draft_entity, 'rdf_draft');
    $page['#pre_render'][] = [$this, 'buildTitle'];
    $page['#entity_type'] = $entity->getEntityTypeId();
    $page['#' . $page['#entity_type']] = $draft_entity;

    return $page;
  }

  /**
   * Build the page title.
   *
   * @param array $page
   *   Render array.
   *
   * @return array
   *   Render array.
   */
  public function buildTitle(array $page) {
    $entity_type = $page['#entity_type'];
    $entity = $page['#' . $entity_type];
    // If the entity's label is rendered using a field formatter, set the
    // rendered title field formatter as the page title instead of the default
    // plain text title. This allows attributes set on the field to propagate
    // correctly (e.g. RDFa, in-place editing).
    if ($entity instanceof FieldableEntityInterface) {
      $label_field = $entity->getEntityType()->getKey('label');
      if (isset($page[$label_field])) {
        $page['#title'] = $this->renderer->render($page[$label_field]);
      }
    }
    return $page;
  }

}
