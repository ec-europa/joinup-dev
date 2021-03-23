<?php

declare(strict_types = 1);

namespace Drupal\rdf_draft;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\sparql_entity_storage\SparqlEntityStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions for RDF graphs.
 */
class RdfGraphPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new dynamic permissions instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Returns an array of graph view permissions.
   *
   * @return array
   *   The SPARQL graph view permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function getRdfGraphPermissions() {
    $perms = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      if ($storage instanceof SparqlEntityStorage) {
        $definitions = $storage->getGraphDefinitions();
        unset($definitions['default']);
        foreach ($definitions as $name => $definition) {
          $perms += $this->buildPermissions($entity_type, $name);
        }
      }
    }

    return $perms;
  }

  /**
   * Returns a list of permissions per entity type and graph.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $type
   *   The entity type.
   * @param string $graph
   *   The machine name for the graph.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(EntityTypeInterface $type, $graph) {
    $type_id = $type->id();
    $type_params = [
      '%type_name' => $type->getLabel(),
      '%graph_name' => $graph,
    ];

    return [
      "view $type_id $graph graph" => [
        'title' => $this->t('%type_name: View %graph_name graph', $type_params),
      ],
    ];
  }

}
