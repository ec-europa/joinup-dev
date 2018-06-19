<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\pipeline\PipelineStepWithBatchTrait;
use Drupal\pipeline\Plugin\PipelineStepBatchInterface;
use Drupal\pipeline\Plugin\PipelineStepWithFormInterface;
use Drupal\pipeline\Plugin\PipelineStepWithFormTrait;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfEntitySparqlStorageInterface;
use Drupal\rdf_entity\RdfInterface;
use Drupal\rdf_entity_provenance\ProvenanceHelperInterface;
use Drupal\rdf_schema_field_validation\SchemaFieldValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a step plugin allowing the user to select certain solutions.
 *
 * @PipelineStep(
 *   id = "user_selection_filter",
 *   label = @Translation("User selection"),
 * )
 */
class UserSelectionFilter extends JoinupFederationStepPluginBase implements PipelineStepWithFormInterface, PipelineStepBatchInterface {

  use PipelineStepWithFormTrait;

  use PipelineStepWithBatchTrait;

  const BATCH_SIZE = 25;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The RDF entity provenance helper service.
   *
   * @var \Drupal\rdf_entity_provenance\ProvenanceHelperInterface
   */
  protected $provenanceHelper;

  /**
   * The date/time formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

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
   * The RDF entity storage.
   *
   * @var \Drupal\rdf_entity\RdfEntitySparqlStorageInterface
   */
  protected $rdfStorage;

  /**
   * The RDF entity query.
   *
   * @var \Drupal\rdf_entity\Entity\Query\Sparql\SparqlQueryInterface
   */
  protected $rdfEntityQuery;

  /**
   * The incoming entities whitelist.
   *
   * @var array
   */
  protected $whitelist = [];

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
   * @param \Drupal\rdf_entity_provenance\ProvenanceHelperInterface $rdf_entity_provenance_helper
   *   The RDF entity provenance helper service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date/time formatter service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\rdf_schema_field_validation\SchemaFieldValidatorInterface $rdf_schema_field_validator
   *   The RDF schema field validator service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $sparql, EntityTypeManagerInterface $entity_type_manager, ProvenanceHelperInterface $rdf_entity_provenance_helper, DateFormatterInterface $date_formatter, AccountProxyInterface $current_user, EntityFieldManagerInterface $entity_field_manager, SchemaFieldValidatorInterface $rdf_schema_field_validator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $sparql);
    $this->entityTypeManager = $entity_type_manager;
    $this->provenanceHelper = $rdf_entity_provenance_helper;
    $this->dateFormatter = $date_formatter;
    $this->currentUser = $current_user;
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
      $container->get('rdf_entity_provenance.provenance_helper'),
      $container->get('date.formatter'),
      $container->get('current_user'),
      $container->get('entity_field.manager'),
      $container->get('rdf_schema_field_validation.schema_field_validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute(array &$data) {
    if ($this->getProgress()->needsInitialisation()) {
      // If this is the first time this method is fired, check if the user has
      // not selected anything to import.
      if (empty(array_filter($data['user_selection']))) {
        return [
          '#markup' => $this->t("You didn't select any solution. As a consequence, no entity has been imported."),
        ];
      }
      $this->initializeBatch($data);
    }
    $this->executeBatch($data);
    return NULL;
  }

  /**
   * Executes the batch process for the user selection filter form submission.
   *
   * @param array $data
   *   An array of data.
   */
  protected function executeBatch(array &$data): void {
    $batch_data = $this->getProgress()->getData();
    $all_imported_ids = $batch_data['whitelist'];
    if (empty($all_imported_ids)) {
      $this->getProgress()->setCompleted();
      return;
    }

    $ids_to_process = array_splice($all_imported_ids, 0, self::BATCH_SIZE);
    $activities = $this->provenanceHelper->loadOrCreateEntitiesActivity($ids_to_process);

    // The $id is the id of the referenced entity, not the activity entity.
    // @see \Drupal\rdf_entity_provenance\ProvenanceHelperInterface::loadOrCreateEntitiesActivity.
    foreach ($activities as $id => $activity) {
      $activity
        // Set the last user that federated this entity as owner.
        ->setOwnerId($this->currentUser->id())
        // Update the provenance based on user input.
        ->set('provenance_enabled', in_array($id, $batch_data['whitelist']))
        ->save();
    }

    $this->getProgress()->setBatchIteration($this->getProgress()->getBatchIteration() + count($ids_to_process));
    $batch_data['whitelist'] = $all_imported_ids;
    $this->getProgress()->setData($batch_data);
  }

  /**
   * Initializes the batch process.
   *
   * @param array $data
   *   The batch data array.
   */
  protected function initializeBatch(array &$data): void {
    $user_selection = $data['user_selection'];
    if (!$user_selection_is_empty = empty(array_filter($user_selection))) {
      // Build a list of all whitelisted entities and stores it in the batch
      // progress data array.
      $this->buildWhitelist('solution', array_keys(array_filter($user_selection)));
    }

    // Remove the blacklisted entities, if any.
    $batch_data = $this->getProgress()->getData();
    $all_incoming_ids = $this->getRdfEntityQuery()->graphs(['staging'])->execute();
    if ($blacklist = array_diff($all_incoming_ids, $batch_data['whitelist'])) {
      $this->getRdfStorage()->deleteFromGraph(Rdf::loadMultiple($blacklist), 'staging');
    }

    $this->getProgress()->setBatchIteration(0);
    $this->getProgress()->setTotalBatchIterations(count($batch_data['whitelist']));
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $entities_per_category = $this->getEntitiesByCategory();

    $form['description'] = [
      '#markup' => $this->t("Check the solutions that you wish to import. Unselected solutions will be saved to 'Blacklisted Solutions' section on the next import attempt. You can always import a blacklisted solution by selecting it."),
    ];

    $options = $default_value = [];
    foreach ($entities_per_category as $category_id => $entities) {
      foreach ($entities as $id => $label) {
        $options[$id] = [
          'solution' => $label,
          'info' => $this->getInfo($id, $category_id),
          '#attributes' => ['data-drupal-federation-category' => $category_id],
        ];
        $default_value[$id] = $category_id !== 'blacklisted' ? $id : NULL;
      }
      $options += $entities;
    }

    $form['user_selection'] = [
      '#type' => 'tableselect',
      '#options' => $options,
      '#header' => [
        'solution' => $this->t('Solution'),
        'info' => $this->t('Info'),
      ],
      '#empty' => $this->t('This import contains no incoming entities. Nothing will be imported.'),
      '#default_value' => $default_value,
      '#after_build' => [
        // We'll append a new pre-render callback.
        [static::class, 'alterFormPreRender'],
      ],
      // Temporary disable the "(un)select all" checkbox because of a bug in the
      // main theme that makes this functionality not to work properly.
      // @todo Remove this line in ISAICP-4545.
      // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4545
      '#js_select' => FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function extractDataFromSubmit(FormStateInterface $form_state) {
    // Normalize user selection to boolean values.
    return [
      'user_selection' => array_map(function ($checked): bool {
        return (bool) $checked;
      }, $form_state->getValue('user_selection')),
    ];
  }

  /**
   * Collect all the whitelisted incoming entities.
   *
   * The user selects only solutions on the form of this pipeline step. But each
   * solution might refer other entities, such as distributions, contact info,
   * publishers, etc. And the latest might also refer other entities, defining a
   * relation of type 'directed acyclic graph', with nested relations. Based on
   * user selected solutions, we build a whitelist of solutions and their
   * related entities. The whitelist is stored in the `$this->whitelist`
   * protected property to be used later by the caller method.
   *
   * If a subsequent entity is referred, directly or through a nested relation,
   * by both, a whitelisted and a blacklisted solution, then this entity will be
   * whitelisted.
   *
   * The caller will take care to delete from the 'staging' graph the entities
   * that are not in the `$this->whitelist` and to create/update disabled
   * provenance activity records.
   *
   * @param string $bundle
   *   The bundle of the passed whitelisted entity IDs. As this method is called
   *   recursively, this value will be computed on each call, except the first
   *   call when it should be 'solution'.
   * @param string[] $whitelist_ids
   *   A list of whitelisted entity IDs. All entities are from $bundle bundle.
   *   The caller should pass the list of whitelisted solution IDs.
   * @param array|null $whitelisted_solution_ids
   *   Used internally to distinguish between caller calls and recursive calls.
   *
   * @throws \InvalidArgumentException
   *   If on first call to the method, something different than 'solution' has
   *   been passed as $bundle parameter or if a passed item from $whitelist_ids
   *   is not from $bundle bundle.
   */
  protected function buildWhitelist(string $bundle, array $whitelist_ids, ?array $whitelisted_solution_ids = NULL): void {
    $data = $this->getProgress()->getData();
    if (!isset($data['whitelist'])) {
      $data['whitelist'] = [];
    }
    static $reference_fields = [];

    // Compute the whitelist of IDs not already added but exit early if empty.
    if (!$new_whitelist_ids = array_diff($whitelist_ids, $data['whitelist'])) {
      return;
    }

    // Add new whitelisted IDs.
    $data['whitelist'] = array_merge($data['whitelist'], $new_whitelist_ids);
    $this->getProgress()->setData($data);

    // Store once the top level whitelisted solutions.
    if (!$whitelisted_solution_ids) {
      if ($bundle !== 'solution') {
        throw new \InvalidArgumentException("First call of ::buildWhitelist() should always receive 'solution' as \$bundle parameter ('$bundle' was passed).");
      }
      $whitelisted_solution_ids = $whitelist_ids;
    }

    // Build and statically cache a list of reference fields, part of ADMS-AP,
    // for this bundle.
    if (!isset($reference_fields[$bundle])) {
      $reference_fields[$bundle] = [];
      foreach ($this->entityFieldManager->getFieldDefinitions('rdf_entity', $bundle) as $field_name => $field_definition) {
        if (
          $field_definition->getType() === 'entity_reference'
          && $field_definition->getFieldStorageDefinition()->getSetting('target_type') === 'rdf_entity'
          && !$field_definition->isComputed()
          && $this->rdfSchemaFieldValidator->isDefinedInSchema('rdf_entity', $bundle, $field_name)
        ) {
          $reference_fields[$bundle][] = $field_name;
        }
      }
    }
    // This bundle has no entity reference fields.
    if (!$reference_fields[$bundle]) {
      return;
    }

    /** @var \Drupal\rdf_entity\RdfInterface $entity */
    foreach (Rdf::loadMultiple($new_whitelist_ids, ['staging']) as $id => $entity) {
      if ($entity->bundle() !== $bundle) {
        throw new \InvalidArgumentException("::buildWhitelist() was called for bundle '$bundle' but the passed ID '$id' is from '{$entity->bundle()}'.");
      }
      foreach ($reference_fields[$bundle] as $field_name) {
        /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field */
        $field = $entity->get($field_name);
        foreach ($this->getReferencedEntityIdsByBundle($field) as $referenced_bundle => $referenced_entity_ids) {
          // The list might contain blacklisted solutions.
          if ($referenced_bundle === 'solution' && array_diff($referenced_entity_ids, $whitelisted_solution_ids)) {
            // Remove blacklisted solutions.
            if (!$referenced_entity_ids = array_intersect($referenced_entity_ids, $whitelisted_solution_ids)) {
              continue;
            }
          }
          $this->buildWhitelist($referenced_bundle, $referenced_entity_ids, $whitelisted_solution_ids);
        }
      }
    }
  }

  /**
   * Returns a list of entity IDs grouped by bundle, given a reference field.
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field
   *   The entity reference field.
   *
   * @return array[]
   *   An associative array keyed by bundle and having arrays of IDs as values.
   */
  protected function getReferencedEntityIdsByBundle(EntityReferenceFieldItemListInterface $field): array {
    $return = [];
    if (!$field->isEmpty()) {
      // Can't use EntityReferenceFieldItemListInterface::referencedEntities()
      // here because that doesn't filter on 'staging' graph.
      $ids = array_filter(array_unique(array_map(function (array $item): string {
        return $item['target_id'];
      }, $field->getValue())));
      if ($ids) {
        /** @var \Drupal\rdf_entity\RdfInterface $entity */
        foreach (Rdf::loadMultiple($ids, ['staging']) as $id => $entity) {
          $return[$entity->bundle()][] = $id;
        }
      }
    }
    return $return;
  }

  /**
   * Returns a list of RDF entities from the staging graph, grouped by category.
   *
   * @return string[]
   *   Associative array keyed by category ID. The values are associative arrays
   *   keyed by entity ID and having the entity labels as values.
   */
  protected function getEntitiesByCategory(): array {
    $ids = $this->getRdfEntityQuery()
      ->graphs(['staging'])
      ->condition('rid', 'solution')
      ->execute();

    $activities = $this->provenanceHelper->loadOrCreateEntitiesActivity($ids);
    $labels = [];
    /** @var \Drupal\rdf_entity\RdfInterface $solution */
    foreach (Rdf::loadMultiple($ids, ['staging']) as $id => $solution) {
      $category = $this->getCategory($activities[$id]);
      $labels[$category][$id] = $solution->label() . " [{$solution->id()}]";
    }

    return $labels;
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
    // If the provenance activity record is new, there was no previous attempt
    // to federate this solution.
    if ($activity->isNew()) {
      return 'not_federated';
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
   * Returns federation information about a given entity.
   *
   * @param string $id
   *   The entity ID.
   * @param string $category
   *   The solution's category.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The solution label.
   *
   * @throws \Exception
   *   If the passed $category is unknown.
   */
  protected function getInfo(string $id, string $category): MarkupInterface {
    $activity = $this->provenanceHelper->loadOrCreateEntityActivity($id);
    $arguments = [
      '%last_user' => $activity->getOwnerId() ? $activity->getOwner()->getDisplayName() : $this->t('[unknown]'),
      '%last_date' => !$activity->get('provenance_started')->isEmpty() ? $this->dateFormatter->format($activity->get('provenance_started')->value, 'short') : $this->t('[unknown]'),
    ];
    switch ($category) {
      case 'not_federated':
        return $this->t('Not federated yet');

      case 'federated':
        return $this->t('Federated on %last_date by %last_user', $arguments);

      case 'blacklisted':
        return $this->t('Blacklisted on %last_date by %last_user', $arguments);
    }
    throw new \Exception("Unknown category '$category'.");
  }

  /**
   * Returns data about the imported solutions categories.
   *
   * @param string $category
   *   The category for which to return metadata.
   *
   * @return \Drupal\Component\Render\MarkupInterface[]
   *   Associative array with two keys, 'label' and 'description'.
   */
  protected static function getCategoryMetadata(string $category): array {
    return [
      'not_federated' => [
        'label' => t('Solutions never federated'),
        'description' => t("These are solutions on the first attempt to be federated. Unselecting them will prevent this import and will make them visible on the 'Blacklisted Section' below on a future import attempt."),
      ],
      'federated' => [
        'label' => t('Federated solutions'),
        'description' => t("These are solutions that were already federated and are part of the Joinup. Unselecting them will prevent this import and will make them visible on the 'Blacklisted Section' below on a future import attempt."),
      ],
      'blacklisted' => [
        'label' => t('Blacklisted solutions'),
        'description' => t('The import of these solutions was attempted in the past but they were blacklisted by the user who instantiated the import. If you want to import blacklisted solutions, just select them.'),
      ],
    ][$category];
  }

  /**
   * Alters the table element, providing an additional pre-render callback.
   *
   * @param array $element
   *   The element to be altered.
   *
   * @return array
   *   The altered element.
   */
  public static function alterFormPreRender(array $element): array {
    $element['#pre_render'][] = [static::class, 'insertRowCategories'];
    return $element;
  }

  /**
   * Inserts category grouping rows.
   *
   * @param array $element
   *   The element to be altered.
   *
   * @return array
   *   The altered element.
   */
  public static function insertRowCategories(array $element): array {
    $new_rows = [];
    $previous_category = NULL;
    foreach ($element['#rows'] as $row) {
      $category = $row['data-drupal-federation-category'];
      if ($category !== $previous_category) {
        $info = static::getCategoryMetadata($category);
        $group = [
          'data' => [
            [
              '#prefix' => '<h4>',
              '#suffix' => '</h4>',
              '#markup' => $info['label'],
            ],
            [
              '#prefix' => '<p>',
              '#suffix' => '</p>',
              '#markup' => $info['description'],
            ],
          ],
          'colspan' => 3,
        ];
        $new_rows[] = [$group];
      }
      $new_rows[] = $row;
      $previous_category = $category;
    }
    $element['#rows'] = $new_rows;

    return $element;
  }

  /**
   * Returns the RDF entity storage.
   *
   * @return \Drupal\rdf_entity\RdfEntitySparqlStorageInterface
   *   The RDF entity storage.
   */
  protected function getRdfStorage(): RdfEntitySparqlStorageInterface {
    if (!isset($this->rdfStorage)) {
      $this->rdfStorage = $this->entityTypeManager->getStorage('rdf_entity');
    }
    return $this->rdfStorage;
  }

  /**
   * Returns the statically cached RDF entity query.
   *
   * @return \Drupal\rdf_entity\Entity\Query\Sparql\SparqlQueryInterface
   *   The RDF entity query.
   */
  protected function getRdfEntityQuery() {
    if (!isset($this->rdfEntityQuery)) {
      $this->rdfEntityQuery = $this->getRdfStorage()->getQuery();
    }
    return $this->rdfEntityQuery;
  }

}
