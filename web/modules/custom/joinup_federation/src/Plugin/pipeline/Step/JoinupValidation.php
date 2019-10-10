<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\pipeline\Exception\PipelineStepExecutionLogicException;
use Drupal\pipeline\Plugin\PipelineStepWithBatchTrait;
use Drupal\pipeline\Plugin\PipelineStepWithBatchInterface;
use Drupal\solution\Plugin\Validation\Constraint\UniqueSolutionTitleConstraint;
use Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Performs Drupal validation against incoming entities.
 *
 * @PipelineStep(
 *   id = "joinup_validation",
 *   label = @Translation("Joinup compliance validation"),
 * )
 */
class JoinupValidation extends JoinupFederationStepPluginBase implements PipelineStepWithBatchInterface {

  use PipelineStepWithBatchTrait;

  /**
   * The batch size.
   *
   * @var int
   */
  const BATCH_SIZE = 5;

  /**
   * Non-critical violations map.
   *
   * The first level keys are the constraint plugin IDs. The second level keys
   * are the RDF entity bundles. The values are field names.
   *
   * @var array
   *
   * @todo Expand the list to cover all cases.
   */
  protected static $nonCriticalViolationWhitelist = [
    'NotNull' => [
      'solution' => [
        'field_is_banner',
        'field_is_logo',
        'field_is_solution_type',
        'field_policy_domain',
      ],
      'asset_release' => [
        'field_isr_banner',
        'field_isr_logo',
        'field_policy_domain',
      ],
    ],
  ];

  /**
   * The constraint plugin manager service.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $constraintManager;

  /**
   * A list of non-critical violations metadata, keyed by entity ID.
   *
   * @var array
   */
  protected $nonCriticalViolations = [];

  /**
   * Creates a new pipeline step plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface $connection
   *   The SPARQL database connection.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $constraint_manager
   *   The constraint plugin manager service.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, ConnectionInterface $connection, PluginManagerInterface $constraint_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $connection);
    $this->constraintManager = $constraint_manager;
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
      $container->get('validation.constraint')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function initBatchProcess() {
    $ids = array_keys($this->getPersistentDataValue('entities'));
    $this->setBatchValue('remaining_ids', $ids);
    return ceil(count($ids) / static::BATCH_SIZE);
  }

  /**
   * {@inheritdoc}
   */
  public function batchProcessIsCompleted() {
    return !$this->getBatchValue('remaining_ids');
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $ids = $this->extractNextSubset('remaining_ids', static::BATCH_SIZE);
    $rows = [];
    /** @var \Drupal\rdf_entity\RdfInterface $entity */
    foreach (Rdf::loadMultiple($ids, ['staging']) as $entity) {
      if ($messages = $this->getViolationsMessages($entity)) {
        $rows[] = [
          [
            'colspan' => 2,
            'data' => [
              [
                '#markup' => $this->t("@type: %name", [
                  '@type' => $entity->get('rid')->entity->label(),
                  '%name' => $entity->label() ? ($entity->label() . ' [' . $entity->id() . ']') : $entity->id(),
                ]),
                '#prefix' => '<h3>',
                '#suffix' => '</h3>',
              ],
            ],
          ],
        ];

        foreach ($messages as $message) {
          $rows[] = [
            [
              'data' => $message['field'] ?? $this->t('N/A'),
            ],
            $message['message'],
          ];
        }
      }
    }

    // Store non-critical violation metadata in the pipeline persistent data
    // store to be displayed at the end of the pipeline execution.
    $this->setPersistentDataValue('non_critical_violations', $this->nonCriticalViolations);

    if ($rows) {
      throw (new PipelineStepExecutionLogicException())->setError($rows);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildBatchProcessErrorMessage() {
    $rows = array_reduce($this->getBatchErrorMessages(), function (array $rows, array $row_group): array {
      return array_merge($rows, $row_group);

    }, []);

    if (!$rows) {
      return $rows;
    }

    return [
      '#theme' => 'table',
      '#header' => [
        $this->t('Field'),
        $this->t('Message'),
      ],
      '#rows' => $rows,
    ];
  }

  /**
   * Returns a list of critical violations metadata for a given entity.
   *
   * The method also collects the non-critical violations metadata for the
   * passed entity. The ::execute() method will write this information in the
   * pipeline persistent data store to be displayed at the end of the pipeline
   * execution.
   *
   * @param \Drupal\rdf_entity\RdfInterface $entity
   *   The entity to be validated.
   *
   * @return array[]
   *   The constraint violation list.
   */
  protected function getViolationsMessages(RdfInterface $entity): array {
    $critical_violations = $non_critical_violations = [];

    $violations = $entity->validate();
    // If the solution does not have a label, an invalid query will be
    // constructed in the validation check. Avoid breaking the site for broken
    // data.
    if (!empty($entity->label()) && $constraint = $this->getUniqueTitleViolation($entity)) {
      $violations->add($constraint);
    }

    if (!$violations->count()) {
      return [];
    }

    // Process first the entity violations.
    $entity_violations = $violations->getEntityViolations();

    /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
    foreach ($entity_violations as $violation) {
      $critical_violations[] = [
        'message' => $violation->getMessage(),
      ];
    }
    // Process the field violations.
    foreach ($violations->getFieldNames() as $field_name) {
      foreach ($violations->getByField($field_name) as $violation) {
        $field_label = $entity->getFieldDefinition($field_name)->getLabel();
        $message = [
          'message' => $violation->getMessage(),
          'field' => [
            '#markup' => $field_label,
            '#prefix' => '<span title="' . $field_label . ' (' . $field_name . ')">',
            '#suffix' => '</span>',
          ],
        ];
        if ($this->isCritical($entity, $field_name, $violation)) {
          $critical_violations[] = $message;
        }
        else {
          $non_critical_violations[] = $message;
        }
      }
    }

    if ($non_critical_violations) {
      $this->nonCriticalViolations[$entity->id()] = $non_critical_violations;
    }

    return $critical_violations;
  }

  /**
   * Checks if validation violation, for given field, is critical.
   *
   * @param \Drupal\rdf_entity\RdfInterface $entity
   *   The entity being validated.
   * @param string $field_name
   *   The field name.
   * @param \Symfony\Component\Validator\ConstraintViolationInterface $violation
   *   The violation.
   *
   * @return bool
   *   If this violation is critical.
   */
  protected function isCritical(RdfInterface $entity, string $field_name, ConstraintViolationInterface $violation): bool {
    $constraint_plugin_id = $this->getConstraintPluginIdFromViolation($violation);
    return empty(static::$nonCriticalViolationWhitelist[$constraint_plugin_id][$entity->bundle()]) || !in_array($field_name, static::$nonCriticalViolationWhitelist[$constraint_plugin_id][$entity->bundle()]);
  }

  /**
   * Returns the constraint plugin ID given a violation object.
   *
   * @param \Symfony\Component\Validator\ConstraintViolationInterface $violation
   *   The violation object.
   *
   * @return string
   *   The constraint plugin ID.
   */
  protected function getConstraintPluginIdFromViolation(ConstraintViolationInterface $violation): string {
    static $constraints_map;

    if (!isset($constraints_map)) {
      $constraints_map = [];
      foreach ($this->constraintManager->getDefinitions() as $id => $plugin_definition) {
        $constraints_map[$plugin_definition['class']] = $id;
      }
    }

    return $constraints_map[get_class($violation->getConstraint())];
  }

  /**
   * Checks for the unique title constraint of solutions.
   *
   * Solutions have a unique title constraint set up for the label property.
   * However, the default constraint only checks against the default and draft
   * graphs and ignores the staging as a non active one. There is no point to
   * change the constraint to also include the staging graph as there might be
   * stale data of a broken import.
   *
   * @param \Drupal\rdf_entity\RdfInterface $entity
   *   The entity to check for unique title.
   *
   * @return \Symfony\Component\Validator\ConstraintViolationInterface|null
   *   The title violation or null if there is no validation.
   */
  protected function getUniqueTitleViolation(RdfInterface $entity): ?ConstraintViolationInterface {
    // The $check_unaffiliated_collections in the following function is set
    // to true to avoid trying to compute the 'collection' property which
    // might not be computable yet. The query is run against the staging
    // graph only so there should not be solutions from other collections
    // there.
    if ($entity->bundle() !== 'solution' || solution_title_is_unique($entity, TRUE, ['staging'])) {
      return NULL;
    }

    $parameters = ['%value' => $entity->label()];
    return new ConstraintViolation(
      $this->t('A solution titled %value already exists in this collection. Please choose a different title.', $parameters),
      '',
      [],
      '',
      'label',
      'invalid',
      NULL,
      NULL,
      new UniqueSolutionTitleConstraint([])
    );
  }

}
