<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\joinup_federation\JoinupFederationStepPluginBase;
use Drupal\rdf_entity\Database\Driver\sparql\Connection;
use Drupal\rdf_schema_field_validation\SchemaFieldValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Removes the references to entities blacklisted in the previous step.
 *
 * @PipelineStep(
 *   id = "remove_references_to_blacklist",
 *   label = @Translation("Remove references to not-imported entities"),
 * )
 */
class RemoveReferencesToBlacklist extends JoinupFederationStepPluginBase {

  use AdmsSchemaEntityReferenceFieldsTrait;
  use SparqlEntityStorageTrait;

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
   * @param \Drupal\rdf_entity\Database\Driver\sparql\Connection $sparql
   *   The SPARQL database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\rdf_schema_field_validation\SchemaFieldValidatorInterface $rdf_schema_field_validator
   *   The RDF schema field validator service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, Connection $sparql, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, SchemaFieldValidatorInterface $rdf_schema_field_validator) {
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
  public function execute() {
    if ($blacklist = array_flip($this->getPersistentDataValue('blacklist'))) {
      $ids = array_keys($this->getPersistentDataValue('entities'));
      /** @var \Drupal\rdf_entity\RdfInterface $entity */
      foreach ($this->getRdfStorage()->loadMultiple($ids, ['staging']) as $id => $entity) {
        $changed = FALSE;
        foreach ($this->getAdmsSchemaEntityReferenceFields($entity->bundle()) as $field_name) {
          /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field */
          $field = $entity->get($field_name);
          $changed |= $this->removeBlacklistedReferences($field, $blacklist);
        }
        if ($changed) {
          $entity->skip_notification = TRUE;
          $entity->save();
        }
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
   * @return bool
   *   If at least one field item has been removed.
   */
  protected function removeBlacklistedReferences(EntityReferenceFieldItemListInterface $field, array $blacklist): bool {
    $changed = FALSE;

    if (!$field->isEmpty()) {
      $deltas_to_remove = [];
      foreach ($field as $delta => $field_item) {
        if (isset($blacklist[$field_item->target_id])) {
          $deltas_to_remove[] = $delta;
        }
      }
      if ($deltas_to_remove) {
        foreach ($deltas_to_remove as $delta_to_remove) {
          $field->removeItem($delta_to_remove);
        }
        $changed = TRUE;
      }
    }

    return $changed;
  }

}
