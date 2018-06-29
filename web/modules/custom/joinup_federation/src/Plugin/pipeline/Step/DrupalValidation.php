<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Performs Drupal validation against incoming entities.
 *
 * @PipelineStep(
 *   id = "drupal_validation",
 *   label = @Translation("Drupal validation"),
 * )
 */
class DrupalValidation extends JoinupFederationStepPluginBase {

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
    'BrokenReferences' => [
      'solution' => [
        'field_is_affiliations_requests',
        'field_is_related_solutions',
      ],
      'asset_release' => [
        'field_isr_related_solutions',
      ],
    ],
    'OptionalToMandatory' => [
      'solution' => [
        'field_is_contact_information',
      ],
      'asset_release' => [
        // Changelog entry #17. Cardinality change from 0..n to 1..n.
        'field_isr_contact_information',
      ],
      'asset_distribution' => [
        // Changelog entry #40. Cardinality change from 0..n to 1..n.
        'field_ad_licence',
      ],
      'owner' => [
        // Changelog entry #15. Cardinality change from 0..n to 1..n.
        'label',
      ],
      'contact_information' => [
        // Changelog entry #54. Changed from recommended to mandatory.
        // In our conversion, the property 'formattedName' is converted into the
        // 'fn' property which is the predicate of the label so we cover some
        // cases and we might avoid having to provide workarounds for that.
        'label',
      ],
    ],
    'NonDocumented' => [
      'contact_information' => [
        // While this is not documented in the changelog, it was discovered that
        // in ADMS v1, the contact information entity has an email property that
        // had a cardinality of 0..n. The corresponding 'hasEmail' property in
        // ADMS v2 has a cardinality of 1..n.
        'field_ci_email',
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
   * @param \Drupal\rdf_entity\Database\Driver\sparql\Connection $sparql
   *   The SPARQL database connection.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $constraint_manager
   *   The constraint plugin manager service.
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, Connection $sparql, PluginManagerInterface $constraint_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $sparql);
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
  public function execute() {
    $rows = [];
    $ids = array_keys($this->getPersistentDataValue('entities'));

    // Reset rdf entity cache.
    \Drupal::entityTypeManager()->getStorage('rdf_entity')->resetCache();

    /** @var \Drupal\rdf_entity\RdfInterface $entity */
    foreach (Rdf::loadMultiple($ids, ['staging']) as $id => $entity) {
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

    if ($rows) {
      return [
        '#theme' => 'table',
        '#header' => [
          $this->t('Field'),
          $this->t('Message'),
        ],
        '#rows' => $rows,
      ];
    }

    // Store non-critical violation metadata in the pipeline persistent data
    // store to be displayed at the end of the pipeline execution.
    $this->setPersistentDataValue('non_critical_violations', $this->nonCriticalViolations);
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

}
