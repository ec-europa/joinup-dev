<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\pipeline\Plugin\PipelineStepWithFormInterface;
use Drupal\pipeline\Plugin\PipelineStepWithFormTrait;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfEntitySparqlStorageInterface;
use Drupal\rdf_entity\RdfInterface;
use Drupal\rdf_entity_provenance\ProvenanceHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a step plugin that allows the user to un-select certain entities.
 *
 * @PipelineStep(
 *   id = "user_selection_filter",
 *   label = @Translation("User selection"),
 * )
 */
class UserSelectionFilter extends JoinupFederationStepPluginBase implements PipelineStepWithFormInterface {

  use PipelineStepWithFormTrait;

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
   * The RDF entity storage.
   *
   * @var \Drupal\rdf_entity\RdfEntitySparqlStorageInterface
   */
  protected $rdfStorage;

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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $sparql, EntityTypeManagerInterface $entity_type_manager, ProvenanceHelperInterface $rdf_entity_provenance_helper, DateFormatterInterface $date_formatter, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $sparql);
    $this->entityTypeManager = $entity_type_manager;
    $this->provenanceHelper = $rdf_entity_provenance_helper;
    $this->dateFormatter = $date_formatter;
    $this->currentUser = $current_user;
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
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute(array &$data) {
    if (!$data['user_selection']) {
      return ['#markup' => $this->t('No entities were imported.')];
    }

    // Normalize user selection to boolean values.
    $user_selection = array_map(function ($checked): bool {
      return (bool) $checked;
    }, $data['user_selection']);

    $activities = $this->provenanceHelper->getProvenanceByReferredEntities(array_keys($user_selection));
    foreach ($activities as $id => $activity) {
      $activity
        // Set the last user that federated this entity as owner.
        ->setOwnerId($this->currentUser->id())
        // Update the provenance based on user input.
        ->set('provenance_enabled', $user_selection[$id])
        ->save();
    }

    // Remove the blacklisted entities from the 'staging' graph.
    $blacklist = array_keys(array_filter($user_selection, function (bool $checked): bool {
      return !$checked;
    }));
    $this->getRdfStorage()->deleteFromGraph(Rdf::loadMultiple($blacklist), 'staging');

    return NULL;
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
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function extractDataFromSubmit(FormStateInterface $form_state) {
    return ['user_selection' => $form_state->getValue('user_selection')];
  }

  /**
   * Returns a list of RDF entities from the staging graph, grouped by category.
   *
   * @return string[]
   *   Associative array keyed by category ID. The values are associative arrays
   *   keyed by entity ID and having the entity labels as values.
   */
  protected function getEntitiesByCategory(): array {
    /** @var \Drupal\rdf_entity\Entity\Query\Sparql\SparqlQueryInterface $query */
    $query = $this->getRdfStorage()->getQuery();
    $ids = $query->graphs(['staging'])->condition('rid', 'solution')->execute();

    $activities = $this->provenanceHelper->getProvenanceByReferredEntities($ids);
    $labels = array_fill_keys(['not_federated', 'federated', 'blacklisted'], []);
    /** @var \Drupal\rdf_entity\RdfInterface $solution */
    foreach (Rdf::loadMultiple($ids, ['staging']) as $id => $solution) {
      $category = $this->getCategory($activities[$id]);
      $labels[$category][$id] = $solution->label();
    }

    // Don't return empty categories.
    return array_filter($labels);
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
   * Returns federation metadata about a given entity.
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
   *   If $category is unknown.
   */
  protected function getInfo(string $id, string $category): MarkupInterface {
    $activity = $this->provenanceHelper->getProvenanceByReferredEntity($id);
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

}
