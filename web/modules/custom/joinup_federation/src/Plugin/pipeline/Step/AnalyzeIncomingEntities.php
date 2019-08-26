<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\joinup_federation\JoinupFederationHashGenerator;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\pipeline\Plugin\PipelineStepWithBatchInterface;
use Drupal\pipeline\Plugin\PipelineStepWithBatchTrait;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfInterface;
use Drupal\rdf_entity_provenance\ProvenanceHelperInterface;
use Drupal\rdf_schema_field_validation\SchemaFieldValidatorInterface;
use Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface;
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
 *   id = "analyze_incoming_entities",
 *   label = @Translation("Analyze the incoming solutions"),
 * )
 */
class AnalyzeIncomingEntities extends JoinupFederationStepPluginBase implements PipelineStepWithBatchInterface {

  use AdmsSchemaEntityReferenceFieldsTrait;
  use IncomingEntitiesDataHelperTrait;
  use PipelineStepWithBatchTrait;
  use SparqlEntityStorageTrait;

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
   * The hash generator service.
   *
   * @var \Drupal\joinup_federation\JoinupFederationHashGenerator
   */
  protected $hashGenerator;

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
   * @param \Drupal\joinup_federation\JoinupFederationHashGenerator $hash_generator
   *   The hash generator service.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, ConnectionInterface $sparql, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ProvenanceHelperInterface $provenance_helper, SchemaFieldValidatorInterface $rdf_schema_field_validator, JoinupFederationHashGenerator $hash_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $sparql);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->provenanceHelper = $provenance_helper;
    $this->rdfSchemaFieldValidator = $rdf_schema_field_validator;
    $this->hashGenerator = $hash_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('sparql_endpoint'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('rdf_entity_provenance.provenance_helper'),
      $container->get('rdf_schema_field_validation.schema_field_validator'),
      $container->get('joinup_federation.hash_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function initBatchProcess(): int {
    $incoming_ids = $this->getAllIncomingIds();
    $this->setBatchValue('ids_to_process', $incoming_ids);
    $this->setBatchValue('solution_ids', $this->getIncomingSolutionIds());
    return (int) ceil(count($incoming_ids) / self::BATCH_SIZE);
  }

  /**
   * {@inheritdoc}
   */
  public function batchProcessIsCompleted(): bool {
    return empty($this->getBatchValue('ids_to_process'));
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $ids = $this->extractNextSubset('ids_to_process', 50);
    $solution_ids = array_intersect($ids, $this->getBatchValue('solution_ids'));

    // Skip calculation of the same ids.
    $hash_ids = array_diff($ids, $this->getEntityIdsWithHashes());
    $this->setEntityHashes($this->hashGenerator->generateDataHash($hash_ids));

    // Handle the solutions and their dependencies of this iteration.
    $storage = $this->getRdfStorage();

    /** @var \Drupal\rdf_entity\RdfInterface[] $entities */
    $entities = $storage->loadMultiple($solution_ids, ['staging']);
    foreach ($entities as $id => $entity) {
      $this->addSolutionDataRoot($id);
      $this->buildSolutionDependencyTree($entity, $id);
    }
    $this->buildSolutionsCategories($solution_ids);

    // Store data in the persistent state.
    $this->storeEntityData();
  }

  /**
   * Builds a dependency tree of a solution.
   *
   * @param \Drupal\rdf_entity\RdfInterface $entity
   *   The rdf entity currently checking for relations.
   * @param string $parent_id
   *   The solution parent. Set in the root of the tree.
   */
  protected function buildSolutionDependencyTree(RdfInterface $entity, string $parent_id): void {
    // If the entity is already in the list of entities of the solution, return
    // early. The entity is already processed.
    if ($this->hasSolutionDataChildDependency($parent_id, $entity)) {
      return;
    }

    // If the entity is a solution, do not descend further because each solution
    // has its own dependencies. Related solutions do not affect the solution
    // itself.
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

      foreach ($this->getStagingReferencedEntities($field) as $referenced_entity) {
        if ($this->hasSolutionDataChildDependency($parent_id, $referenced_entity)) {
          // The entity has already been processed.
          continue;
        }

        // Only for the case of the release, dive deeper and check whether there
        // are more referenced entities. Normally, the only difference between
        // the solution and the release, would be the distributions.
        if ($referenced_entity->bundle() === 'asset_release') {
          $this->buildSolutionDependencyTree($referenced_entity, $parent_id);
        }

        // Do not add solutions and licences as a dependency. Solutions are a
        // tree on their own and licences are stored in Joinup and are not
        // imported.
        if (!in_array($referenced_entity->bundle(), ['licence', 'solution'])) {
          $this->addSolutionDataChildDependency($parent_id, $referenced_entity);
        }
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
   *
   * @param array $solution_ids
   *   A list of solution ids for which to calculate the category.
   */
  protected function buildSolutionsCategories(array $solution_ids): void {
    foreach ($solution_ids as $solution_id) {
      $provenance_record = $this->provenanceHelper->loadOrCreateEntityActivity($solution_id);
      $category = $this->getCategory($provenance_record);
      $this->setSolutionCategory($solution_id, $category);
    }
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
      // In case the entity is marked as federated, check all entities related
      // to the entity. If none of them changed, mark it as unchanged.
      $entity_list = $this->getSolutionsWithDependenciesAsFlatList([$activity->get('provenance_entity')->value]);
      if (!$this->isSolutionChanged($entity_list)) {
        return 'federated_unchanged';
      }

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
   *   it has changed.
   * - The provenance record of the entity is new. This meanst the entity has
   *   not been federated before.
   * - The entity's changed time is more recent than the provenance record's
   *   provenance_started property.
   *
   * @param string[] $entity_ids
   *   A list of entity ids that the solution is related to.
   *
   * @return bool
   *   Whether the solution or one of its related entities have changed since
   *   the last import.
   */
  protected function isSolutionChanged(array $entity_ids): bool {
    /** @var \Drupal\rdf_entity\RdfInterface[] $solutions */
    $solutions = $this->getRdfStorage()->loadMultiple($entity_ids, ['staging']);
    $provenance_records = $this->provenanceHelper->loadOrCreateEntitiesActivity($entity_ids);

    foreach ($solutions as $id => $solution) {
      // Licences are stored in Joinup so changes do not affect incoming
      // solution.
      if ($solution->bundle() === 'licence') {
        continue;
      }

      // Like the parent solution, if this is the first time the solution is
      // imported, the solution is marked as new.
      if ($provenance_records[$id]->isNew() || $provenance_records[$id]->get('provenance_hash')
        ->isEmpty()) {
        return TRUE;
      }

      $entity_hash = $this->getEntityHash($id);
      if ($entity_hash !== $provenance_records[$id]->provenance_hash->value) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Sets the solution category to the solution data.
   *
   * @param string $solution_id
   *   The solution id.
   * @param string $category
   *   The solution category.
   */
  protected function setSolutionCategory(string $solution_id, string $category): void {
    $this->solutionData[$solution_id]['category'] = $category;
  }

  /**
   * Stores the entity data to the persistent pipeline state.
   */
  protected function storeEntityData(): void {
    $this->setPersistentDataValue('incoming_solution_data', $this->solutionData);
    $this->setPersistentDataValue('entity_hashes', $this->entityHashes);
  }

  /**
   * Adds a solution id on the root of the solution data array.
   *
   * This method does not check if the root already exists and initializes the
   * entry as a new array.
   *
   * @param string $solution_id
   *   The solution entity id.
   */
  protected function addSolutionDataRoot(string $solution_id): void {
    $this->solutionData[$solution_id] = [];
  }

  /**
   * Sets an entity as a dependency to the structured data.
   *
   * @param string $parent_id
   *   The parent solution entity id.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The child entity.
   */
  protected function addSolutionDataChildDependency(string $parent_id, EntityInterface $entity): void {
    $this->solutionData[$parent_id]['dependencies'][$entity->bundle()][$entity->id()] = $entity->id();
  }

  /**
   * Returns whether the given solution has the given entity as a dependency.
   *
   * @param string $parent_id
   *   The parent solution id.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The candidate child entity.
   *
   * @return bool
   *   Whether the solution already has this entity listed as a dependency.
   */
  protected function hasSolutionDataChildDependency(string $parent_id, EntityInterface $entity): bool {
    return isset($this->solutionData[$parent_id]['dependencies'][$entity->bundle()][$entity->id()]);
  }

  /**
   * Returns an array of ids that have hashes calculated.
   *
   * @return array
   *   An array of entity ids.
   */
  protected function getEntityIdsWithHashes(): array {
    return array_keys($this->entityHashes);
  }

  /**
   * Adds the passed hashes to the list of hashes.
   *
   * @param array $data
   *   An associative array of hashes indexed by the related entity id.
   */
  protected function setEntityHashes(array $data): void {
    $this->entityHashes += $data;
  }

}
