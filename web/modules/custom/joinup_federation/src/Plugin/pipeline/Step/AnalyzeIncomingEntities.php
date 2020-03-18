<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\joinup_federation\JoinupFederationHashGenerator;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\pipeline\Plugin\PipelineStepInterface;
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
 * Related entities can be releases, distributions, licences, publishers, etc.
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
   * Used to build a list of solution's dependencies to be stored in pipeline.
   *
   * An associative array having the solution ID as index. The value is an
   * associative array itself, of entity IDs indexed by their bundle.
   *
   * @var array
   */
  protected $solutionDependency;

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
    parent::__construct($configuration, $plugin_id, $plugin_definition, $sparql, $entity_type_manager);
    $this->entityFieldManager = $entity_field_manager;
    $this->provenanceHelper = $provenance_helper;
    $this->rdfSchemaFieldValidator = $rdf_schema_field_validator;
    $this->hashGenerator = $hash_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): PipelineStepInterface {
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
    $this->setBatchValue('entity_ids', $incoming_ids);
    $this->setBatchValue('solution_ids', $this->getIncomingSolutionIds());
    $this->setPersistentDataValue('entity_hashes', []);
    $this->setPersistentDataValue('solution_dependency', []);
    $this->setPersistentDataValue('solution_category', []);
    return (int) ceil(count($incoming_ids) / self::BATCH_SIZE);
  }

  /**
   * {@inheritdoc}
   */
  public function batchProcessIsCompleted(): bool {
    return empty($this->getBatchValue('entity_ids'));
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $this->solutionDependency = $this->getPersistentDataValue('solution_dependency');

    $ids_to_process = $this->extractNextSubset('entity_ids', static::BATCH_SIZE);
    $solution_ids = array_intersect($ids_to_process, $this->getBatchValue('solution_ids'));

    // Skip calculation of the same IDs.
    $entity_hashes = $this->getPersistentDataValue('entity_hashes');
    $ids_to_hash = array_diff($ids_to_process, array_keys($entity_hashes));
    $entity_hashes += $this->hashGenerator->generateDataHash($ids_to_hash);
    $this->setPersistentDataValue('entity_hashes', $entity_hashes);

    // Handle the solutions and their dependencies of this iteration.
    $solutions = $this->getRdfStorage()->loadMultiple($solution_ids, ['staging']);
    /** @var \Drupal\rdf_entity\RdfInterface $solution */
    foreach ($solutions as $solution_id => $solution) {
      $this->buildSolutionDependencyTree($solution, $solution_id);
    }
    // Store solutions dependencies in the pipeline persistent data store.
    $this->setPersistentDataValue('solution_dependency', $this->solutionDependency);

    // Get and store the solutions categories.
    $this->buildSolutionsCategories($solution_ids);
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
    // If the entity is a solution and is not the parent (the first time
    // entering the recursion), do not descend further because each solution
    // has its own dependencies. Related solutions do not affect the solution
    // itself.
    if ($entity->bundle() === 'solution' && $entity->id() !== $parent_id) {
      return;
    }

    // The first time entering the recursion. Init the solution data array.
    elseif ($entity->bundle() === 'solution' && $entity->id() === $parent_id) {
      $this->solutionDependency[$parent_id] = [];
    }

    // If the entity is already in the list of entities of the solution, return
    // early. The entity is already processed.
    elseif ($this->hasSolutionDataChildDependency($parent_id, $entity)) {
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

        // Dive deeper and check whether there are more referenced entities but
        // not for licences and solutions.
        if ($referenced_entity instanceof RdfInterface && !in_array($referenced_entity->bundle(), ['licence', 'solution'])) {
          $this->buildSolutionDependencyTree($referenced_entity, $parent_id);
        }

        // Do not add solutions and licences as a dependency. Solutions are a
        // tree on their own and licences are stored in Joinup and are not
        // imported.
        if (!in_array($referenced_entity->bundle(), ['licence', 'solution'])) {
          $this->solutionDependency[$parent_id][$referenced_entity->bundle()][$referenced_entity->id()] = $referenced_entity->id();
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
   * Builds an array of categories for solutions.
   *
   * @param array $solution_ids
   *   A list of solution IDs for which to calculate the category.
   */
  protected function buildSolutionsCategories(array $solution_ids): void {
    $solution_category = $this->getPersistentDataValue('solution_category');
    foreach ($solution_ids as $solution_id) {
      $provenance_record = $this->provenanceHelper->loadOrCreateEntityActivity($solution_id);
      $category = $this->getCategory($provenance_record);
      $solution_category[$solution_id] = $category;
    }
    $this->setPersistentDataValue('solution_category', $solution_category);
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
   * Returns whether a solution has changed given a list of entity IDs.
   *
   * @param string[] $entity_ids
   *   A list of entity ids that the solution is related to.
   *
   * @return bool
   *   Whether the solution or one of its related entities have changed since
   *   the last import.
   */
  protected function isSolutionChanged(array $entity_ids): bool {
    /** @var \Drupal\rdf_entity\RdfInterface[] $entities */
    $entities = $this->getRdfStorage()->loadMultiple($entity_ids, ['staging']);
    $provenance_records = $this->provenanceHelper->loadOrCreateEntitiesActivity($entity_ids);

    foreach ($entities as $id => $entity) {
      // Licences are stored in Joinup so changes do not affect incoming
      // solution.
      if ($entity->bundle() === 'licence') {
        continue;
      }

      // Like the parent solution, if this is the first time the solution is
      // imported, the solution is marked as new.
      if ($provenance_records[$id]->isNew() || $provenance_records[$id]->get('provenance_hash')->isEmpty()) {
        return TRUE;
      }

      $entity_hash = $this->getPersistentDataValue('entity_hashes')[$id];
      if ($entity_hash !== $provenance_records[$id]->provenance_hash->value) {
        return TRUE;
      }
    }

    return FALSE;
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
    return isset($this->solutionDependency[$parent_id][$entity->bundle()][$entity->id()]);
  }

  /**
   * Returns the IDs of solutions in the 'staging' graph.
   *
   * @return string[]
   *   An array of IDs.
   */
  protected function getIncomingSolutionIds(): array {
    return $this->getSparqlQuery()
      ->graphs(['staging'])
      ->condition('rid', 'solution')
      ->execute();
  }

}
