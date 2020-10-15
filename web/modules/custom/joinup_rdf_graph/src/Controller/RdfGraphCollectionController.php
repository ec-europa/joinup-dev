<?php

declare(strict_types = 1);

namespace Drupal\joinup_rdf_graph\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\joinup_rdf_graph\RdfGraphListBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides method controllers for RDF graph entity collection.
 */
class RdfGraphCollectionController extends ControllerBase {

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Constructs a new controller instance.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   */
  public function __construct(ContainerInterface $container) {
    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static($container);
  }

  /**
   * Provides a controller method for 'joinup_rdf_graph.collection' route.
   *
   * @return array
   *   The entity list as a render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   If the 'rdf_entity' definition cannot be retrieved.
   */
  public function collection(): array {
    $entity_type = $this->entityTypeManager()->getDefinition('rdf_entity');
    $list_builder = RdfGraphListBuilder::createInstance($this->container, $entity_type);
    return $list_builder->render();
  }

}
