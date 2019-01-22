<?php

declare(strict_types = 1);

namespace Drupal\rdf_entity_provenance\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a field value is unique in combination with other fields.
 *
 * @Constraint(
 *   id = "UniqueFieldGroupInBundle",
 *   label = @Translation("Unique group of fields within a bundle constraint", context = "Validation"),
 * )
 */
class UniqueFieldGroupInBundleConstraint extends Constraint {

  public $message = 'Content with @field_name %value already exists. Please choose a different @field_name.';

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Drupal\rdf_entity_provenance\Plugin\Validation\Constraint\UniqueFieldGroupInBundleValidator';
  }

  /**
   * The bundles for which this constraint applies.
   *
   * @var string
   */
  public $bundles;

  /**
   * The list of fields with their values.
   *
   * @var array
   */
  public $fields;

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption(): string {
    return 'bundles';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions(): array {
    return ['bundles'];
  }

}
