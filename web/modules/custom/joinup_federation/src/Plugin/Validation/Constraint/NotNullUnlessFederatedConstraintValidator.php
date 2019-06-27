<?php

namespace Drupal\joinup_federation\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\TypedData\ListInterface;
use Drupal\Core\TypedData\Validation\TypedDataAwareValidatorTrait;
use Drupal\Core\Validation\Plugin\Validation\Constraint\NotNullConstraintValidator;
use Drupal\rdf_entity\RdfInterface;
use Drupal\rdf_entity_provenance\ProvenanceHelperInterface;
use Drupal\rdf_schema_field_validation\SchemaFieldValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;

/**
 * NotNull constraint validator.
 *
 * Overrides the Drupal NotNull validator to allow empty when it refers to a
 * field that belongs to a federated entity.
 *
 * Used for fields that have taxonomy references that are not provided into, or
 * is not mapped in the federated record.
 */
class NotNullUnlessFederatedConstraintValidator extends NotNullConstraintValidator implements ContainerInjectionInterface {

  use TypedDataAwareValidatorTrait;

  /**
   * The provenance helper service.
   *
   * @var \Drupal\rdf_entity_provenance\ProvenanceHelperInterface
   */
  protected $provenanceHelper;

  /**
   * The field validator service.
   *
   * @var \Drupal\rdf_schema_field_validation\SchemaFieldValidatorInterface
   */
  protected $fieldValidator;

  /**
   * Creates a new validator.
   *
   * @param \Drupal\rdf_entity_provenance\ProvenanceHelperInterface $provenance_helper
   *   The provenance helper service.
   * @param \Drupal\rdf_schema_field_validation\SchemaFieldValidatorInterface $field_validator
   *   The field validator service.
   */
  public function __construct(ProvenanceHelperInterface $provenance_helper, SchemaFieldValidatorInterface $field_validator) {
    $this->provenanceHelper = $provenance_helper;
    $this->fieldValidator = $field_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('rdf_entity_provenance.provenance_helper'),
      $container->get('rdf_schema_field_validation.schema_field_validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    $typed_data = $this->getTypedData();
    if ($typed_data instanceof ListInterface) {
      $parent_entity = $value->getParent()->getEntity();
      // Check for the parent entity being an rdf entity so that we quickly skip
      // other entity types that use the NotNull constraint.
      if (($parent_entity instanceof RdfInterface) && !empty($parent_entity->id())) {
        $entity_type_id = $parent_entity->getEntityTypeId();
        $bundle = $parent_entity->bundle();
        if ($this->fieldValidator->hasSchemaDefinition($entity_type_id, $bundle)
          && $this->fieldValidator->isDefinedInSchema($entity_type_id, $bundle, $typed_data->getName())
          && $this->provenanceHelper->loadProvenanceActivity($parent_entity->id())) {
          return;
        }
      }
    }
    parent::validate($value, $constraint);
  }

}
