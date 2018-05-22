<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use Drupal\rdf_entity\Entity\Query\Sparql\SparqlQueryInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfEntitySparqlStorageInterface;
use Drupal\rdf_entity\RdfInterface;
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

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The RDF entity SPARQL storage.
   *
   * @var \Drupal\rdf_entity\RdfEntitySparqlStorageInterface
   */
  protected $rdfStorage;

  /**
   * The cached SPARQL entity query.
   *
   * @var \Drupal\rdf_entity\Entity\Query\Sparql\SparqlQueryInterface
   */
  protected $sparqlQuery;

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
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\rdf_entity\Database\Driver\sparql\Connection $sparql
   *   The SPARQL database connection.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\rdf_schema_field_validation\SchemaFieldValidatorInterface $rdf_schema_field_validator
   *   The RDF schema field validator service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $sparql, AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, SchemaFieldValidatorInterface $rdf_schema_field_validator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $sparql);
    $this->currentUser = $current_user;
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
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('rdf_schema_field_validation.schema_field_validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute(array &$data) {
    // Get the incoming entities.
    $incoming_ids = $this->getSparqlQuery()
      ->graphs(['staging'])
      ->execute();
    /** @var \Drupal\rdf_entity\RdfInterface[] $incoming_entities */
    $incoming_entities = $incoming_ids ? $this->getRdfStorage()->loadMultiple($incoming_ids, ['staging']) : [];

    // Get the incoming entities that are stored also locally.
    $local_ids = $this->getSparqlQuery()
      ->graphs(['default', 'draft'])
      ->condition('id', array_values($incoming_ids), 'IN')
      ->execute();
    /** @var \Drupal\rdf_entity\RdfInterface[] $local_entities */
    $local_entities = $local_ids ? Rdf::loadMultiple($local_ids) : [];

    /** @var \Drupal\rdf_entity\RdfInterface $incoming_entity */
    foreach ($incoming_entities as $id => $incoming_entity) {
      $bundle = $incoming_entity->bundle();

      // The entity already exists.
      if (isset($local_entities[$id])) {
        $local_entity = $local_entities[$id];

        // Check for bundle mismatch between the local and the incoming entity.
        if ($local_entity->bundle() !== $bundle) {
          $arguments = [
            '%id' => $id,
            '%incoming' => $incoming_entity->get('rid')->entity->getSingularLabel(),
            '%local' => $local_entity->get('rid')->entity->getSingularLabel(),
          ];
          return [
            '#markup' => $this->t("The imported @incoming with the ID '%id' tries to override a local %local with the same ID.", $arguments),
          ];
        }

        $needs_save = $this->updateAdmsFields($local_entity, $incoming_entity);

        // Cleanup the entity from the 'staging' graph.
        $this->getRdfStorage()->deleteFromGraph([$incoming_entity], 'staging');
      }
      // No local entity. Copy the incoming entity as a published entity.
      else {
        $local_entity = (clone $incoming_entity)
          ->enforceIsNew()
          ->setOwnerId($this->currentUser->id());

        // Determine the state field for this bundle, if any.
        $state_field_name = NULL;
        $state_field_map = $this->entityFieldManager->getFieldMapByFieldType('state');
        if (!empty($state_field_map['rdf_entity'])) {
          foreach ($state_field_map['rdf_entity'] as $field_name => $field_info) {
            if (isset($field_info['bundles'][$bundle])) {
              $state_field_name = $field_name;
              break;
            }
          }
        }

        // There are also entities without a state field.
        if ($state_field_name) {
          $local_entity->set($state_field_name, 'validated');
        }
        $local_entity->graph->value = 'default';

        // A new entity needs to be saved.
        $needs_save = TRUE;

        // Delete the incoming entity from the staging graph.
        $incoming_entity->skip_notification = TRUE;
        $incoming_entity->delete();
      }

      if ($needs_save) {
        $local_entity->skip_notification = TRUE;
        $local_entity->save();
      }
    }
  }

  /**
   * Updates the ADMS field values from the incoming to the local entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $local_entity
   *   The local entity.
   * @param \Drupal\rdf_entity\RdfInterface $incoming_entity
   *   The imported entity.
   *
   * @return bool
   *   If the local entity has been changed and needs to be saved.
   */
  protected function updateAdmsFields(RdfInterface $local_entity, RdfInterface $incoming_entity): bool {
    $changed = FALSE;

    foreach ($local_entity->getFieldDefinitions() as $field_name => $field_definition) {
      // Bypass fields without mapping or fields we don't want to override.
      if (in_array($field_name, ['id', 'rid', 'graph', 'uuid', 'uid'])) {
        continue;
      }
      // Only stored fields are allowed.
      if ($field_definition->isComputed()) {
        continue;
      }

      $columns = $field_definition->getFieldStorageDefinition()->getColumns();
      foreach ($columns as $column_name => $column_schema) {
        // Check if the field is an ADMS-AP field.
        if ($this->rdfSchemaFieldValidator->isDefinedInSchema('rdf_entity', $local_entity->bundle(), $field_name, $column_name)) {
          $incoming_field = $incoming_entity->get($field_name);
          $local_field = $local_entity->get($field_name);
          // Assign only if the incoming and local fields are different.
          if (!$local_field->equals($incoming_field)) {
            $local_field->setValue($incoming_field->getValue());
            $changed = TRUE;
            // Don't check the rest of the columns because the whole field has
            // been already assigned.
            break;
          }
        }
      }
    }

    return $changed;
  }

  /**
   * Returns the RDF storage.
   *
   * @return \Drupal\rdf_entity\RdfEntitySparqlStorageInterface
   *   The RDF storage.
   */
  protected function getRdfStorage(): RdfEntitySparqlStorageInterface {
    if (!isset($this->rdfStorage)) {
      $this->rdfStorage = $this->entityTypeManager->getStorage('rdf_entity');
    }
    return $this->rdfStorage;
  }

  /**
   * Returns the SPARQL entity query.
   *
   * @return \Drupal\rdf_entity\Entity\Query\Sparql\SparqlQueryInterface
   *   The entity query.
   */
  protected function getSparqlQuery(): SparqlQueryInterface {
    if (!isset($this->sparqlQuery)) {
      $this->sparqlQuery = $this->getRdfStorage()->getQuery();
    }
    return $this->sparqlQuery;
  }

}
