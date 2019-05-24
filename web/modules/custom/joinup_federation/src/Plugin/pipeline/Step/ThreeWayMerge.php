<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\pipeline\Plugin\PipelineStepWithBatchTrait;
use Drupal\pipeline\Plugin\PipelineStepWithBatchInterface;
use Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfInterface;
use Drupal\rdf_schema_field_validation\SchemaFieldValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Ensures values for empty fields from the staging graph.
 *
 * @PipelineStep(
 *   id = "3_way_merge",
 *   label = @Translation("Set default or local values to empty fields"),
 * )
 */
class ThreeWayMerge extends JoinupFederationStepPluginBase implements PipelineStepWithBatchInterface {

  use PipelineStepWithBatchTrait;
  use SparqlEntityStorageTrait;

  /**
   * The batch size.
   *
   * The three way merge is the heaviest process in the import sequence, thus
   * the batch size is 1.
   *
   * @var int
   */
  const BATCH_SIZE = 1;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The field validator service.
   *
   * @var \Drupal\rdf_schema_field_validation\SchemaFieldValidatorInterface
   */
  protected $fieldValidator;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Creates a new pipeline step plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface $sparql
   *   The SPARQL database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\rdf_schema_field_validation\SchemaFieldValidatorInterface $field_validator
   *   The field validator service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ConnectionInterface $sparql, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, SchemaFieldValidatorInterface $field_validator, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $sparql);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->fieldValidator = $field_validator;
    $this->time = $time;
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
      $container->get('rdf_schema_field_validation.schema_field_validator'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function initBatchProcess() {
    $whitelist = $this->getPersistentDataValue('whitelist');
    $this->unsetPersistentDataValue('whitelist');

    // Get the incoming entities that are stored also locally.
    $local_ids = $this->getSparqlQuery()
      ->condition('id', array_values($whitelist), 'IN')
      ->execute();

    $incoming_ids = [];
    foreach ($whitelist as $id) {
      $incoming_ids[$id] = isset($local_ids[$id]);
    }

    $this->setBatchValue('remaining_incoming_ids', $incoming_ids);

    return ceil(count($incoming_ids) / static::BATCH_SIZE);
  }

  /**
   * {@inheritdoc}
   */
  public function batchProcessIsCompleted() {
    return !$this->getBatchValue('remaining_incoming_ids');
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $ids_to_process = $this->extractNextSubset('remaining_incoming_ids', static::BATCH_SIZE);
    $incoming_ids = array_keys($ids_to_process);
    $local_ids = array_keys(array_filter($ids_to_process));

    /** @var \Drupal\rdf_entity\RdfInterface[] $incoming_entities */
    $incoming_entities = $incoming_ids ? $this->getRdfStorage()->loadMultiple($incoming_ids, ['staging']) : [];
    $local_entities = $local_ids ? Rdf::loadMultiple($local_ids) : [];

    // Collect here entity IDs that are about to be saved.
    $entities = $this->hasPersistentDataValue('entities') ? $this->getPersistentDataValue('entities') : [];
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
        $this->handleDistributionParent($incoming_entity, $entity_exists);
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

      // If the changed timestamp is not present, fill it in even if it is
      // defined in schema.
      if ($field_name === 'changed' && $incoming_entity->get($field_name)->isEmpty() && ($incoming_entity instanceof EntityChangedInterface)) {
        $incoming_entity->set($field_name, $this->time->getRequestTime());
        $changed = TRUE;
        continue;
      }

      // Only stored fields are allowed.
      if ($field_definition->isComputed()) {
        continue;
      }

      // Values of fields defined in ADMS schema must always persist over local
      // values.
      if ($this->fieldValidator->isDefinedInSchema($incoming_entity->getEntityTypeId(), $incoming_entity->bundle(), $field_name)) {
        continue;
      }

      $incoming_field = $incoming_entity->get($field_name);
      $local_field = $local_entity->get($field_name);

      if (!$incoming_field->equals($local_field)) {
        // Always keep the local values for non schema defined field.
        $incoming_field->setValue($local_field->getValue());
        $changed = TRUE;
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

    // Set the state of new entities to 'validated'.
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
   *   The incoming solution.
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
    if (!$collection_id = $this->getPipeline()->getCollection()) {
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
      throw new \Exception("Plugin '3_way_merge' is configured to assign the '$collection_id' collection but the existing solution '{$incoming_solution->id()}' has '{$incoming_solution->collection->target_id}' as collection.");
    }
    // For an existing solution we don't make any changes to its affiliation.
  }

  /**
   * Handles the incoming distribution parent assignment.
   *
   * This is only valid for new distributions. Existing entities should already
   * have the data attached.
   *
   * @param \Drupal\rdf_entity\RdfInterface $incoming_entity
   *   The incoming entity.
   * @param bool $entity_exists
   *   If the incoming entity already exits on the system.
   */
  protected function handleDistributionParent(RdfInterface $incoming_entity, bool $entity_exists): void {
    if ($incoming_entity->bundle() !== 'asset_distribution' || $entity_exists) {
      return;
    }
    // Trigger the computation of the parent.
    $incoming_entity->get('parent')->getValue();
  }

}
