<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfEntityGraphInterface;
use Drupal\rdf_schema_field_validation\SchemaFieldValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Merges the incoming data with local data.
 *
 * @PipelineStep(
 *   id = "3_way_merge",
 *   label = @Translation("3-way merge"),
 * )
 */
class ThreeWayMerge extends JoinupFederationStepPluginBase {

  use SparqlEntityStorageTrait;

  /**
   * Bundle priority on import.
   *
   * @var string[]
   */
  const PRIORITY = [
    'contact_information',
    'licence',
    'owner',
    'solution',
    'asset_release',
    'asset_distribution',
  ];

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The RDF schema field validator service.
   *
   * @var \Drupal\rdf_schema_field_validation\SchemaFieldValidatorInterface
   */
  protected $rdfSchemaFieldValidator;

  /**
   * Creates a new pipeline step plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\rdf_entity\Database\Driver\sparql\Connection $sparql
   *   The SPARQL database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\rdf_schema_field_validation\SchemaFieldValidatorInterface $rdf_schema_field_validator
   *   The RDF schema field validator service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, Connection $sparql, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, SchemaFieldValidatorInterface $rdf_schema_field_validator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $sparql);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->rdfSchemaFieldValidator = $rdf_schema_field_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('sparql_endpoint'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('rdf_schema_field_validation.schema_field_validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $entities_by_bundle = array_fill_keys(static::PRIORITY, []);

    // Retrieve the list of entities from the persistent data store as an
    // associative array keyed by entity ID and having a boolean as value,
    // signaling if the entity already exists in Joinup.
    $entities = $this->getPersistentDataValue('entities');
    $ids = array_keys($entities);

    // Build a list of local entities that are about to be updated.
    $local_entity_ids = array_keys(array_filter($entities));
    /** @var \Drupal\rdf_entity\RdfInterface[] $local_entities */
    // @todo Remove the 2nd argument of ::loadMultiple() in ISAICP-4497.
    // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4497
    $local_entities = $local_entity_ids ? Rdf::loadMultiple($local_entity_ids, [RdfEntityGraphInterface::DEFAULT, 'draft']) : [];

    $deleted_entities = [];
    /** @var \Drupal\rdf_entity\RdfInterface $entity */
    foreach (Rdf::loadMultiple($ids, ['staging']) as $id => $entity) {
      // The entity already exists.
      if ($entities[$id]) {
        $graph_ids = [];
        foreach ([RdfEntityGraphInterface::DEFAULT, 'draft'] as $graph_id) {
          if ($local_entities[$id]->hasGraph($graph_id)) {
            $graph_ids[$graph_id] = $graph_id;
          }
        }

        // Pick up first graph to be set.
        $graph_id = key($graph_ids);
        $local_entity = clone $entity;
        $local_entity->set('graph', $graph_id);

        // If the local entity exists in both, 'default' and 'draft', graphs, we
        // remove the 'draft' version. This is needed because the federated
        // fields cannot be edited locally anymore and a potential publish of
        // the draft could override the federated fields values.
        if (count($graph_ids) > 1) {
          $this->getRdfStorage()->deleteFromGraph([$local_entity], 'draft');
        }

        // Collect the entities to be deleted later from the 'staging' graph. We
        // are not deleting here because, when saving the entities in the main
        // graphs, this would lead to a null $entity->original.
        $deleted_entities[] = $entity;
      }
      // No local entity. Copy the incoming entity as a published entity.
      else {
        $local_entity = (clone $entity)
          ->enforceIsNew()
          ->set('graph', RdfEntityGraphInterface::DEFAULT);
        // Delete the incoming entity from the staging graph.
        $entity->skip_notification = TRUE;
        $entity->delete();
      }
      // Group entities by bundle.
      $entities_by_bundle[$local_entity->bundle()][] = $local_entity;
    }

    // Save the entities.
    foreach ($entities_by_bundle as $bundle => $entities_from_bundle) {
      /** @var \Drupal\rdf_entity\RdfInterface $local_entity */
      foreach ($entities_from_bundle as $local_entity) {
        $local_entity->skip_notification = TRUE;
        $local_entity->save();
      }
    }

    // Cleanup the entity from the 'staging' graph.
    if ($deleted_entities) {
      $this->getRdfStorage()->deleteFromGraph($deleted_entities, 'staging');
    }
  }

}
