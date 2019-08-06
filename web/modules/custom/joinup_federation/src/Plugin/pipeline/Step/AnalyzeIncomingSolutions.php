<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\pipeline\Plugin\PipelineStepWithBatchInterface;
use Drupal\pipeline\Plugin\PipelineStepWithBatchTrait;
use Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfInterface;
use Drupal\rdf_entity_provenance\ProvenanceHelperInterface;
use Drupal\rdf_schema_field_validation\SchemaFieldValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Analyzes the incoming solutions.
 *
 * The result of this step is going to be 2 new persistent array values in the
 * pipeline, the 'solution_dependency_tree' and the 'solutions_categories' that
 * will accompany the wizard.
 *
 * The solution_dependency_tree is an associative array where each value is an
 * array of entity ids that are related to the solution id which is the index.
 * Related entities can be releases, distributions, licences, publishers etc.
 * The array does not contain related solutions.
 *
 * The solutions_categories array is an associative array of categories indexed
 * by the corresponding id.
 *
 * @PipelineStep(
 *   id = "analyze_incoming_solutions",
 *   label = @Translation("Analyze the incoming solutions"),
 * )
 */
class AnalyzeIncomingSolutions extends JoinupFederationStepPluginBase implements PipelineStepWithBatchInterface {

  use AdmsSchemaEntityReferenceFieldsTrait;
  use SparqlEntityStorageTrait;
  use PipelineStepWithBatchTrait;

  /**
   * The batch size.
   *
   * @var int
   */
  const BATCH_SIZE = 50;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The provenance helper service.
   *
   * @var \Drupal\rdf_entity_provenance\ProvenanceHelperInterface
   */
  protected $provenanceHelper;

  /**
   * The RDF schema field validator service.
   *
   * @var \Drupal\rdf_schema_field_validation\SchemaFieldValidatorInterface
   */
  protected $rdfSchemaFieldValidator;

  /**
   * A dependency tree for each incoming solution.
   *
   * Each entry is a flat list of entity ids that each solution (the index) is
   * related to.
   *
   * @var array
   */
  protected $solutionDependencyTree = [];

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
   * @param \Drupal\rdf_entity_provenance\ProvenanceHelperInterface $provenance_helper
   *   The provenance helper service.
   * @param \Drupal\rdf_schema_field_validation\SchemaFieldValidatorInterface $rdf_schema_field_validator
   *   The RDF schema field validator service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ConnectionInterface $sparql, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ProvenanceHelperInterface $provenance_helper, SchemaFieldValidatorInterface $rdf_schema_field_validator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $sparql);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->provenanceHelper = $provenance_helper;
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
      $container->get('rdf_entity_provenance.provenance_helper'),
      $container->get('rdf_schema_field_validation.schema_field_validator')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function initBatchProcess() {
    $incoming_solution_ids = $this->getIncomingSolutionIds();
    $this->setBatchValue('ids_to_process', $incoming_solution_ids);
    $this->setPersistentDataValue('solution_dependency_tree', []);
    $this->setPersistentDataValue('solutions_categories', []);

    return ceil(count($incoming_solution_ids) / self::BATCH_SIZE);
  }

  /**
   * {@inheritdoc}
   */
  public function batchProcessIsCompleted() {
    return empty($this->getBatchValue('ids_to_process'));
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $ids = $this->extractNextSubset('ids_to_process', 50);
    $this->solutionDependencyTree = $this->getPersistentDataValue('solution_dependency_tree');
    $storage = $this->getRdfStorage();

    /** @var \Drupal\rdf_entity\RdfInterface[] $entities */
    $entities = $storage->loadMultiple($ids, ['staging']);
    foreach ($entities as $id => $entity) {
      $this->buildSolutionDependencyTree($entity, $entity->id());
    }

    $this->buildSolutionsCategories();
    $this->setPersistentDataValue('solution_dependency_tree', $this->solutionDependencyTree);
  }



  /**
   * Builds a dependency tree of a solution.
   *
   * @param \Drupal\rdf_entity\RdfInterface $entity
   *   The rdf entity currently checking for relations.
   * @param string $parent
   *   The solution parent. Set in the root of the tree.
   */
  protected function buildSolutionDependencyTree(RdfInterface $entity, string $parent): void {
    // If this is the first time it is called, enter the solution id in the
    // dependency tree.
    if ($entity->id() === $parent && !isset($this->solutionDependencyTree[$parent])) {
      $this->solutionDependencyTree[$parent] = [];
    }

    // If the entity is already in the list of entities of the solution, return
    // early. The entity is already processed.
    elseif (in_array($entity->id(), $this->solutionDependencyTree[$parent])) {
      return;
    }

    // If the entity is a solution, it is a different entry and should not
    // affect the current solution.
    elseif ($entity->bundle() === 'solution') {
      return;
    }

    // This bundle has no entity reference fields.
    if (!$reference_fields = array_keys($this->getAdmsSchemaEntityReferenceFields($entity->bundle(), ['rdf_entity']))) {
      return;
    }

    foreach ($reference_fields as $field_name) {
      /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field */
      $field = $entity->get($field_name);

      foreach ($this->getStagingReferencedEntities($field) as $id => $referenced_entity) {
        if (in_array($id, $this->solutionDependencyTree)) {
          // The entity has already been processed.
          continue;
        }

        // Only for the case of the release, dive deeper and check whether there
        // are more referenced entities. Normally, the only difference between
        // the solution and the release, would be the distributions.
        if ($referenced_entity->bundle() === 'asset_release') {
          $this->buildSolutionDependencyTree($referenced_entity, $parent);
        }

        $this->solutionDependencyTree[$parent][] = $id;
      }
    }
  }

  /**
   * Returns a list of entity IDs, given a reference field.
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field
   *   The entity reference field.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of entities referenced.
   */
  protected function getStagingReferencedEntities(EntityReferenceFieldItemListInterface $field): array {
    $ids = [];
    if (!$field->isEmpty()) {
      // Can't use EntityReferenceFieldItemListInterface::referencedEntities()
      // here because that doesn't filter on 'staging' graph.
      $ids = array_filter(array_unique(array_map(function (array $item): string {
        return $item['target_id'];
      }, $field->getValue())));
    }
    return Rdf::loadMultiple($ids, ['staging']);
  }

  /**
   * Builds an array of categories for solutions stored in the dependency tree.
   */
  protected function buildSolutionsCategories(): void {
    $solutions_categories = $this->getPersistentDataValue('solutions_categories');

    foreach ($this->solutionDependencyTree as $parent => $related_entity_ids) {
      $provenance_record = $this->provenanceHelper->loadOrCreateEntityActivity($parent);
      $category = $this->getCategory($provenance_record);
      // In case the entity is marked as federated, check all entities related
      // to the entity. If none of them changed, mark it as unchanged.
      if ($category === 'federated') {
        $entity_list = [$parent] + $related_entity_ids;
        if (!$this->getSolutionHasChanged($entity_list)) {
          $category = 'unchanged_federated';
        }
      }

      $solutions_categories[$parent] = $category;
    }
    $this->setPersistentDataValue('solutions_categories', $solutions_categories);
  }

  /**
   * Computes the category of a solution given its provenance activity record.
   *
   * @param \Drupal\rdf_entity\RdfInterface $activity
   *   The solution provenance activity.
   *
   * @return string
   *   The category ID.
   */
  protected function getCategory(RdfInterface $activity): string {
    $collection_id = $this->getPipeline()->getCollection();

    // If the provenance activity record is new, there was no previous attempt
    // to federate this solution.
    if ($activity->isNew()) {
      return 'not_federated';
    }
    // If the solution is already associated with another collection, we can't
    // federate it in the scope of this pipeline's collection.
    elseif ($activity->get('provenance_associated_with')->value !== $collection_id) {
      return 'invalid_collection';
    }
    // If there is an existing provenance activity enabled record, this incoming
    // entity has been previously federated.
    elseif ($activity->get('provenance_enabled')->value) {
      return 'federated';
    }
    // Otherwise this solution has been previously blacklisted.
    return 'blacklisted';
  }

  /**
   * Returns whether a solution has changed given a list of entity ids.
   *
   * The solution will be considered as changed if one of the following occur
   * for at least one of the entries:
   * - The entity does not have a changed property. This means we cannot know if
   * it has changed.
   * - The provenance record of the entity is new. This meanst the entity has
   * not been federated before.
   * - The entity's changed time is more recent than the provenance record's
   * provenance_started property.
   *
   * @param array $entity_ids
   *   A list of entity ids that the solution is related to.
   *
   * @return bool
   *   Whether the solution or one of its related entities have changed since
   *   the last import.
   */
  protected function getSolutionHasChanged(array $entity_ids): bool {
    $storage = $this->getRdfStorage();
    /** @var \Drupal\rdf_entity\RdfInterface[] $entities */
    $entities = $storage->loadMultiple($entity_ids, ['staging']);
    $provenance_records = $this->provenanceHelper->loadOrCreateEntitiesActivity($entity_ids);

    foreach ($entities as $id => $entity) {
      // Licences are stored in Joinup so changes do not affect incoming
      // solution.
      if ($entity->bundle() === 'licence') {
        continue;
      }
      if (!$entity->hasField('changed') || empty($entity->getChangedTime())) {
        return TRUE;
      }

      if ($provenance_records[$id]->isNew()) {
        return TRUE;
      }

      if ($entity->getChangedTime() > $provenance_records[$id]->provenance_started->value) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
