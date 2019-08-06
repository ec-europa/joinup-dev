<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\pipeline\Exception\PipelineStepExecutionLogicException;
use Drupal\pipeline\Plugin\PipelineStepWithClientRedirectResponseTrait;
use Drupal\pipeline\Plugin\PipelineStepWithFormInterface;
use Drupal\pipeline\Plugin\PipelineStepWithFormTrait;
use Drupal\pipeline\Plugin\PipelineStepWithResponseInterface;
use Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface;
use Drupal\rdf_entity\Entity\Rdf;
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
    $this->buildWhitelist($selected_solution_ids);

    // Get all imported entity IDs by running a SPARQL query.
    /** @var \EasyRdf\Sparql\Result $results */
    $results = $this->sparql->query("SELECT DISTINCT(?entityId) WHERE { GRAPH <{$this->getGraphUri('sink')}> { ?entityId ?p ?o . } }");
    $all_imported_ids = array_map(function (\stdClass $item): string {
      return $item->entityId->getUri();
    }, $results->getArrayCopy());

    // Remove the blacklisted entities, if any.
    if ($blacklist = array_values(array_diff($all_imported_ids, $this->whitelist, $this->getUnchangedSolutionIds()))) {
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
        $default_value[$id] = in_array($category_id, ['blacklisted', 'federated_unchanged', 'invalid_collection']) ? NULL : $id;
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
   * @param string[] $whitelist_ids
   *   A list of whitelisted entity IDs. All entities are from $bundle bundle.
   *   The caller should pass the list of whitelisted solution IDs.
   */
  protected function buildWhitelist(array $whitelist_ids): void {
    $solution_dependency_tree = $this->getPersistentDataValue('solution_dependency_tree');
    foreach ($whitelist_ids as $solution_id) {
      $this->whitelist[] = $solution_id;
      $this->whitelist = array_merge($this->whitelist, $solution_dependency_tree[$solution_id]);
    }
  }

  /**
   * Returns a list of RDF entities from the staging graph, grouped by category.
   *
   * @return string[]
   *   Associative array keyed by category ID. The values are associative arrays
   *   keyed by entity ID and having the entity labels as values.
   */
  protected function getEntitiesByCategory(): array {
    $solutions_categories = $this->getPersistentDataValue('solutions_categories');
    $labels = [];
    /** @var \Drupal\rdf_entity\RdfInterface $solution */
    foreach (Rdf::loadMultiple(array_keys($solutions_categories), ['staging']) as $id => $solution) {
      $category = $solutions_categories[$id];
      $label = $solution->label() ?: '<' . $this->t('missing label') . '>';
      $labels[$category][$id] = $label . ' [' . $solution->id() . ']';
    }

    return $labels;
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

      case 'unchanged_federated':
        return $this->t('No changes since the federation on %last_date by %last_user', $arguments);

      case 'blacklisted':
        return $this->t('Blacklisted on %last_date by %last_user', $arguments);
    }
    throw new \Exception("Unknown category '$category'.");
  }

  /**
   * Returns the list of unchanged entities as a list of solution ids.
   *
   * @return array
   *   The solution entity ids.
   */
  protected function getUnchangedSolutionIds(): array {
    $solutions_categories = $this->getPersistentDataValue('solutions_categories');
    $return = [];

    foreach ($solutions_categories as $solution_id => $category) {
      if ($category === 'federated_unchanged') {
        $return[] = $category;
      }
    }
    return $return;
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
      'federated_unchanged' => [
        'label' => t('No changes'),
        'description' => t("These are solutions that were already federated and there are no changes since the last import."),
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
