<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup_federation\StagingCandidateGraphsInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides specific graph functionality for the 'rdf_entity' entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:rdf_entity",
 *   label = @Translation("RDF entity selection"),
 *   entity_types = {"rdf_entity"},
 *   group = "default",
 *   weight = 1
 * )
 */
class RdfEntitySelection extends DefaultSelection {

  /**
   * The staging candidate graphs service.
   *
   * @var \Drupal\joinup_federation\StagingCandidateGraphsInterface
   */
  protected $stagingCandidateGraphs;

  /**
   * Constructs a new selection object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\joinup_federation\StagingCandidateGraphsInterface $staging_candidate_graphs
   *   The staging candidate graphs service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user, StagingCandidateGraphsInterface $staging_candidate_graphs) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager, $module_handler, $current_user);
    $this->stagingCandidateGraphs = $staging_candidate_graphs;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('joinup_federation.staging_candidate_graphs')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableEntities(array $ids) {
    if ($this->isRdfEntityInStagingGraph()) {
      $result = [];
      if ($ids) {
        /** @var \Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlQueryInterface $query */
        $query = $this->buildEntityQuery();
        $result = $query
          ->graphs($this->stagingCandidateGraphs->getCandidates())
          ->condition('id', $ids, 'IN')
          ->execute();
      }
      return $result;
    }
    return parent::validateReferenceableEntities($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableNewEntities(array $entities) {
    $entities = parent::validateReferenceableNewEntities($entities);
    if ($this->isRdfEntityInStagingGraph()) {
      $entities = array_filter($entities, function (RdfInterface $entity): bool {
        return in_array($entity->get('graph')->target_id, $this->stagingCandidateGraphs->getCandidates());
      });
    }
    return $entities;
  }

  /**
   * Checks if the host entity is an RDF entity from the 'staging' graph.
   *
   * @return bool
   *   If the host entity is an RDF entity from the 'staging' graph.
   */
  protected function isRdfEntityInStagingGraph() {
    /** @var \Drupal\rdf_entity\RdfInterface $host_entity */
    $host_entity = $this->configuration['entity'] ?? NULL;
    return $host_entity && ($host_entity->getEntityTypeId() === 'rdf_entity') && ($host_entity->get('graph')->target_id === 'staging');
  }

}
