<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use Drupal\rdf_entity\Entity\Rdf;
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

  use SparqlEntityStorageTrait;

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
   * @param mixed $plugin_definition
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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $sparql, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, SchemaFieldValidatorInterface $rdf_schema_field_validator) {
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

        $this->ensureFieldDefaults($local_entity);

        // A new entity needs to be saved.
        $needs_save = TRUE;

        // Delete the incoming entity from the staging graph.
        $incoming_entity->skip_notification = TRUE;
        $incoming_entity->delete();
        // @todo Call $local_entity->validate() to catch also Drupal violations.
      }

      if ($needs_save) {
        $this->handleAffiliation($local_entity);
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
   * Handles the incoming solution affiliation.
   *
   * For existing solutions, we only check if the configured collection ID
   * matches the solution affiliation. For new solutions, we affiliate the
   * solution to the configured collection.
   *
   * @param \Drupal\rdf_entity\RdfInterface $local_solution
   *   The local solution.
   *
   * @throws \Exception
   *   If the configured collection is different than the collection of the
   *   local solution.
   */
  protected function handleAffiliation(RdfInterface $local_solution): void {
    // Check only solutions.
    if ($local_solution->bundle() !== 'solution') {
      return;
    }

    // If this plugin was not configured to assign a collection, exit early.
    if (!$collection_id = $this->getConfiguration()['collection']) {
      return;
    }

    if ($local_solution->isNew()) {
      $local_solution->set('collection', $collection_id);
      return;
    }

    // Check for collection mismatch when federating an existing solution.
    $match = FALSE;
    foreach ($local_solution->get('collection') as $item) {
      if ($item->target_id === $collection_id) {
        $match = TRUE;
        break;
      }
    }

    if (!$match) {
      throw new \Exception("Plugin '3_way_merge' is configured to assign the '$collection_id' collection but the existing solution '{$local_solution->id()}' has '{$local_solution->collection->target_id}' as collection.");
    }
    // For an existing solution we don't make any changes to its affiliation.
  }

  /**
   * Sets default values for fields.
   *
   * @param \Drupal\rdf_entity\RdfInterface $local_entity
   *   The local entity.
   */
  protected function ensureFieldDefaults(RdfInterface $local_entity): void {
    // Determine the state field for this bundle, if any.
    $state_field_name = NULL;
    $state_field_map = $this->entityFieldManager->getFieldMapByFieldType('state');
    if (!empty($state_field_map['rdf_entity'])) {
      foreach ($state_field_map['rdf_entity'] as $field_name => $field_info) {
        if (isset($field_info['bundles'][$local_entity->bundle()])) {
          $state_field_name = $field_name;
          break;
        }
      }
    }

    // There are also entities without a state field.
    if ($state_field_name) {
      $local_entity->set($state_field_name, 'validated');
    }
    $local_entity->set('graph', 'default');

    /** @var \Drupal\Core\Field\FieldItemListInterface $field */
    foreach ($local_entity as $field_name => &$field) {
      // Populate empty fields with their default value. This is a Drupal
      // content entity that was not created via Drupal API. As an effect,
      // empty fields didn't receive their default values. We have to
      // explicitly do this before saving.
      // The check if the field is a computed field - that occurs first, is due
      // to the fact that we want to avoid the computation of the field in case
      // the field is indeed a computed field. Method "::isEmpty" triggers
      // the computation.
      if (!$field->getFieldDefinition()->isComputed() && $field->isEmpty()) {
        $field->applyDefaultValue();
      }
    }
  }

}
