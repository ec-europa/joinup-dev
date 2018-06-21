<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Ensures values for empty fields from the staging graph.
 *
 * @PipelineStep(
 *   id = "empty_fields_values",
 *   label = @Translation("Empty fields values"),
 * )
 */
class EmptyFieldsValues extends JoinupFederationStepPluginBase {

  use SparqlEntityStorageTrait;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

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
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, Connection $sparql, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $sparql);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
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
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['collection' => NULL] + parent::defaultConfiguration();
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

    // Collect here entity IDs that are about to be saved.
    $entities = [];

    /** @var \Drupal\rdf_entity\RdfInterface $incoming_entity */
    foreach ($incoming_entities as $id => $incoming_entity) {
      $entity_exists = isset($local_entities[$id]);
      // The entity already exists.
      if ($entity_exists) {
        // Check for bundle mismatch between the incoming and the local entity.
        if ($incoming_entity->bundle() !== $local_entities[$id]->bundle()) {
          $arguments = [
            '%id' => $id,
            '@incoming' => $incoming_entity->get('rid')->entity->getSingularLabel(),
            '@local' => $local_entities[$id]->get('rid')->entity->getSingularLabel(),
          ];
          return [
            '#markup' => $this->t("The imported @incoming with the ID '%id' tries to override a local @local with the same ID.", $arguments),
          ];
        }

        $needs_save = $this->updateStagingFieldsFromLocalValues($incoming_entity, $local_entities[$id]);
      }
      // No local entity. Ensure defaults.
      else {
        $this->ensureFieldDefaults($incoming_entity);
        $needs_save = TRUE;
      }

      if ($needs_save) {
        $this->handleAffiliation($incoming_entity, $entity_exists);
        $incoming_entity->skip_notification = TRUE;
        $incoming_entity->save();
        $entities[$incoming_entity->id()] = $entity_exists;
      }
    }

    // Persist the list so we can reuse it in the next steps.
    $this->setPersistentDataValue('entities', $entities);
  }

  /**
   * Updates the field values from the local to the incoming entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $incoming_entity
   *   The imported entity.
   * @param \Drupal\rdf_entity\RdfInterface $local_entity
   *   The local entity.
   *
   * @return bool
   *   If the incoming entity has been changed and needs to be saved.
   */
  protected function updateStagingFieldsFromLocalValues(RdfInterface $incoming_entity, RdfInterface $local_entity): bool {
    $changed = FALSE;

    foreach ($incoming_entity->getFieldDefinitions() as $field_name => $field_definition) {
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
        $incoming_field = $incoming_entity->get($field_name);
        $local_field = $local_entity->get($field_name);
        // Assign only if the incoming field is empty.
        if ($incoming_field->isEmpty()) {
          $incoming_field->setValue($local_field->getValue());
          $changed = TRUE;
          // Don't check the rest of the columns because the whole field has
          // been already assigned.
          break;
        }
      }
    }

    return $changed;
  }

  /**
   * Sets default values for fields.
   *
   * @param \Drupal\rdf_entity\RdfInterface $incoming_entity
   *   The imported entity.
   */
  protected function ensureFieldDefaults(RdfInterface $incoming_entity): void {
    // Determine the state field for this bundle, if any.
    $state_field_name = NULL;
    $state_field_map = $this->entityFieldManager->getFieldMapByFieldType('state');
    if (!empty($state_field_map['rdf_entity'])) {
      foreach ($state_field_map['rdf_entity'] as $field_name => $field_info) {
        if (isset($field_info['bundles'][$incoming_entity->bundle()])) {
          $state_field_name = $field_name;
          break;
        }
      }
    }

    // Set he state of new entities to 'validated'.
    if ($state_field_name) {
      $incoming_entity->set($state_field_name, 'validated');
    }

    /** @var \Drupal\Core\Field\FieldItemListInterface $field */
    foreach ($incoming_entity as $field_name => &$field) {
      // Populate empty fields with their default value. This is a Drupal
      // content entity that was not created via Drupal API. As an effect, empty
      // fields didn't receive their default values. We have to explicitly do
      // this before saving. The check if the field is a computed field is there
      // in order to avoid the computation of the field. The method ::isEmpty()
      // triggers the computation.
      // @see \Drupal\Core\TypedData\ComputedItemListTrait::isEmpty()
      if (!$field->getFieldDefinition()->isComputed() && $field->isEmpty()) {
        $field->applyDefaultValue();
      }
    }
  }

  /**
   * Handles the incoming solution affiliation.
   *
   * For existing solutions, we only check if the configured collection ID
   * matches the solution affiliation. For new solutions, we affiliate the
   * solution to the configured collection.
   *
   * @param \Drupal\rdf_entity\RdfInterface $incoming_solution
   *   The local solution.
   * @param bool $entity_exists
   *   If the incoming entity already exits on the system.
   *
   * @throws \Exception
   *   If the configured collection is different than the collection of the
   *   local solution.
   */
  protected function handleAffiliation(RdfInterface $incoming_solution, bool $entity_exists): void {
    // Check only solutions.
    if ($incoming_solution->bundle() !== 'solution') {
      return;
    }

    // If this plugin was not configured to assign a collection, exit early.
    if (!$collection_id = $this->getConfiguration()['collection']) {
      return;
    }

    if (!$entity_exists) {
      $incoming_solution->set('collection', $collection_id);
      return;
    }

    // Check for collection mismatch when federating an existing solution.
    $match = FALSE;
    foreach ($incoming_solution->get('collection') as $item) {
      if ($item->target_id === $collection_id) {
        $match = TRUE;
        break;
      }
    }

    if (!$match) {
      throw new \Exception("Plugin 'empty_fields_values' is configured to assign the '$collection_id' collection but the existing solution '{$incoming_solution->id()}' has '{$incoming_solution->collection->target_id}' as collection.");
    }
    // For an existing solution we don't make any changes to its affiliation.
  }

}
