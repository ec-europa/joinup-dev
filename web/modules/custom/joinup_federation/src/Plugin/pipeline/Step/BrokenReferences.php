<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\pipeline\Plugin\PipelineStepWithBatchInterface;
use Drupal\pipeline\Plugin\PipelineStepWithBatchTrait;
use Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface;
use Drupal\rdf_schema_field_validation\SchemaFieldValidatorInterface;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Removes the references to entities blacklisted in the previous step.
 *
 * @PipelineStep(
 *   id = "broken_references",
 *   label = @Translation("Remove references to not-imported entities"),
 * )
 */
class BrokenReferences extends JoinupFederationStepPluginBase implements PipelineStepWithBatchInterface {

  use AdmsSchemaEntityReferenceFieldsTrait;
  use PipelineStepWithBatchTrait;
  use SparqlEntityStorageTrait;

  /**
   * The batch size.
   *
   * @var int
   */
  const BATCH_SIZE = 2;

  /**
   * The RDF schema field validator service.
   *
   * @var \Drupal\rdf_schema_field_validation\SchemaFieldValidatorInterface
   */
  protected $rdfSchemaFieldValidator;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

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
   * @param \Drupal\rdf_schema_field_validation\SchemaFieldValidatorInterface $rdf_schema_field_validator
   *   The RDF schema field validator service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ConnectionInterface $sparql, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, SchemaFieldValidatorInterface $rdf_schema_field_validator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $sparql);
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('entity_field.manager'),
      $container->get('rdf_schema_field_validation.schema_field_validator')
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
    $blacklist = array_flip($this->getPersistentDataValue('blacklist'));
    /** @var \Drupal\rdf_entity\RdfInterface $entity */
    foreach ($this->getRdfStorage()->loadMultiple($ids, ['staging']) as $id => $entity) {
      $changed = 0;
      $reference_fields = $this->getAdmsSchemaEntityReferenceFields($entity->bundle(), ['rdf_entity', 'taxonomy_term']);

      foreach ($reference_fields as $field_name => $target_entity_type_id) {
        /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field */
        $field = $entity->get($field_name);

        // Remove references to entities that were blacklisted by the user.
        if ($blacklist && ($target_entity_type_id === 'rdf_entity')) {
          $changed |= $this->removeBlacklistedReferences($field, $blacklist);
        }
        // Remove references to non-existing taxonomy terms.
        elseif ($target_entity_type_id === 'taxonomy_term') {
          $changed |= $this->removeTermsBrokenReferences($field);
        }
      }

      if ($changed) {
        $entity->skip_notification = TRUE;
        $entity->save();
      }
    }
  }

  /**
   * Removes the items referencing blacklisted entities from a field.
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field
   *   The entity reference field item list.
   * @param array $blacklist
   *   The list of blacklisted entity IDs.
   *
   * @return int
   *   If at least one field item has been removed, the value is 1. 0 otherwise.
   */
  protected function removeBlacklistedReferences(EntityReferenceFieldItemListInterface $field, array $blacklist): int {
    $changed = 0;

    if (!$field->isEmpty()) {
      $field->filter(function (FieldItemInterface $field_item) use ($blacklist, &$changed): bool {
        if (isset($blacklist[$field_item->target_id])) {
          $changed = 1;
          return FALSE;
        }
        return TRUE;
      });
    }

    return $changed;
  }

  /**
   * Removes the references to terms that doesn't exists from a field.
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field
   *   The entity reference field item list.
   *
   * @return int
   *   If at least one field item has been removed, the value is 1. 0 otherwise.
   */
  protected function removeTermsBrokenReferences(EntityReferenceFieldItemListInterface $field): int {
    $changed = 0;

    if (!$field->isEmpty()) {
      $existing_term_ids = array_map(function (TermInterface $term): string {
        return $term->id();
      }, $field->referencedEntities());

      $field->filter(function (FieldItemInterface $field_item) use ($existing_term_ids, &$changed): bool {
        if (!in_array($field_item->target_id, $existing_term_ids)) {
          $changed = 1;
          return FALSE;
        }
        return TRUE;
      });
    }

    return $changed;
  }

}
