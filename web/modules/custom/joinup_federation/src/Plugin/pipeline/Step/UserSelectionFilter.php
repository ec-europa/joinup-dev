<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\pipeline\Exception\PipelineStepExecutionLogicException;
use Drupal\pipeline\Plugin\PipelineStepWithClientRedirectResponseTrait;
use Drupal\pipeline\Plugin\PipelineStepWithFormInterface;
use Drupal\pipeline\Plugin\PipelineStepWithFormTrait;
use Drupal\pipeline\Plugin\PipelineStepWithResponseInterface;
use Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfInterface;
use Drupal\rdf_entity_provenance\ProvenanceHelperInterface;
use Drupal\rdf_schema_field_validation\SchemaFieldValidatorInterface;
use Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlArg;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a step plugin allowing the user to select certain solutions.
 *
 * @PipelineStep(
 *   id = "user_selection_filter",
 *   label = @Translation("User selection"),
 * )
 */
class UserSelectionFilter extends JoinupFederationStepPluginBase implements PipelineStepWithFormInterface, PipelineStepWithResponseInterface {

  use AdmsSchemaEntityReferenceFieldsTrait;
  use PipelineStepWithClientRedirectResponseTrait;
  use PipelineStepWithFormTrait;
  use SparqlEntityStorageTrait;

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
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface $sparql
   *   The SPARQL database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\rdf_entity_provenance\ProvenanceHelperInterface $rdf_entity_provenance_helper
   *   The RDF entity provenance helper service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date/time formatter service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\rdf_schema_field_validation\SchemaFieldValidatorInterface $rdf_schema_field_validator
   *   The RDF schema field validator service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ConnectionInterface $sparql, EntityTypeManagerInterface $entity_type_manager, ProvenanceHelperInterface $rdf_entity_provenance_helper, DateFormatterInterface $date_formatter, EntityFieldManagerInterface $entity_field_manager, SchemaFieldValidatorInterface $rdf_schema_field_validator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $sparql);
    $this->entityTypeManager = $entity_type_manager;
    $this->provenanceHelper = $rdf_entity_provenance_helper;
    $this->dateFormatter = $date_formatter;
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
      $container->get('entity_field.manager'),
      $container->get('rdf_schema_field_validation.schema_field_validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $user_selection = $this->getPersistentDataValue('user_selection');
    $this->unsetPersistentDataValue('user_selection');

    // If no solution was selected, exit the pipeline here.
    if (!$selected_solution_ids = array_keys(array_filter($user_selection))) {
      throw (new PipelineStepExecutionLogicException())->setError([
        '#markup' => $this->t("You didn't select any solution. As a consequence, no entity has been imported."),
      ]);
    }

    // Build a list of all whitelisted entities.
    $this->buildWhitelist('solution', $selected_solution_ids);

    // Get all imported entity IDs by running a SPARQL query.
    /** @var \EasyRdf\Sparql\Result $results */
    $results = $this->sparql->query("SELECT DISTINCT(?entityId) WHERE { GRAPH <{$this->getGraphUri('sink')}> { ?entityId ?p ?o . } }");
    $all_imported_ids = array_map(function (\stdClass $item): string {
      return $item->entityId->getUri();
    }, $results->getArrayCopy());

    // Remove the blacklisted entities, if any.
    if ($blacklist = array_values(array_diff($all_imported_ids, $this->whitelist))) {
      $blacklist_ids = SparqlArg::serializeUris($blacklist, ' ');
      $this->sparql->query("WITH <{$this->getGraphUri('sink')}> DELETE { ?entity_id ?p ?o } WHERE { ?entity_id ?p ?o . VALUES ?entity_id { {$blacklist_ids} } }");
    }

    // Persist data for next steps.
    $this
      ->setPersistentDataValue('whitelist', $this->whitelist)
      ->setPersistentDataValue('blacklist', $blacklist);

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
          '#disabled' => $category_id === 'invalid_collection',
        ];
        $default_value[$id] = in_array($category_id, ['blacklisted', 'invalid_collection']) ? NULL : $id;
      }
    }

    $form['user_selection'] = [
      '#type' => 'tableselect',
      '#options' => $options,
      '#header' => [
        'solution' => $this->t('Solution'),
        'info' => $this->t('Info'),
      ],
      '#empty' => $this->t('This import contains no entities that can be federated. Nothing will be imported.'),
      '#default_value' => $default_value,
      '#after_build' => [
        // We'll append a new pre-render callback.
        [static::class, 'alterFormPreRender'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdditionalPersistentDataStore(FormStateInterface $form_state) {
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
   * that are not in the `$this->whitelist`.
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
    // Compute the whitelist of IDs not already added but exit early if empty.
    if (!$new_whitelist_ids = array_diff($whitelist_ids, $this->whitelist)) {
      return;
    }

    // Add new whitelisted IDs.
    $this->whitelist = array_merge($this->whitelist, $new_whitelist_ids);

    // Store once the top level whitelisted solutions.
    if (!$whitelisted_solution_ids) {
      if ($bundle !== 'solution') {
        throw new \InvalidArgumentException("First call of ::buildWhitelist() should always receive 'solution' as \$bundle parameter ('$bundle' was passed).");
      }
      $whitelisted_solution_ids = $whitelist_ids;
    }

    // This bundle has no entity reference fields.
    if (!$reference_fields = array_keys($this->getAdmsSchemaEntityReferenceFields($bundle, ['rdf_entity']))) {
      return;
    }

    /** @var \Drupal\rdf_entity\RdfInterface $entity */
    foreach (Rdf::loadMultiple($new_whitelist_ids, ['staging']) as $id => $entity) {
      if ($entity->bundle() !== $bundle) {
        throw new \InvalidArgumentException("::buildWhitelist() was called for bundle '$bundle' but the passed ID '$id' is from '{$entity->bundle()}'.");
      }
      foreach ($reference_fields as $field_name) {
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
    $ids = $this->getSparqlQuery()
      ->graphs(['staging'])
      ->condition('rid', 'solution')
      ->execute();

    $activities = $this->provenanceHelper->loadOrCreateEntitiesActivity($ids);
    $labels = [];
    /** @var \Drupal\rdf_entity\RdfInterface $solution */
    foreach (Rdf::loadMultiple($ids, ['staging']) as $id => $solution) {
      $category = $this->getCategory($activities[$id]);
      $label = $solution->label() ?: '<' . $this->t('missing label') . '>';
      $labels[$category][$id] = $label . ' [' . $solution->id() . ']';
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
      '%last_user' => $activity->getOwner() ? $activity->getOwner()->getDisplayName() : $this->t('[unknown]'),
      '%last_date' => !$activity->get('provenance_started')->isEmpty() ? $this->dateFormatter->format($activity->get('provenance_started')->value, 'short') : $this->t('[unknown]'),
    ];
    switch ($category) {
      case 'not_federated':
        return $this->t('Not federated yet');

      case 'invalid_collection':
        $associated_with = $this->provenanceHelper->loadActivityAssociatedWith($activity);
        return $this->t('Federation record exists with <a href="@resource_uri" target="_blank">@resource_uri</a>.', [
          '@resource_uri' => $associated_with,
        ]);

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
      'invalid_collection' => [
        'label' => t('Federated in a different collection'),
        'description' => t("These are solutions that were already federated and are part of the Joinup but the parent collection does not match to the one set to be assigned by the current pipeline."),
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

}
