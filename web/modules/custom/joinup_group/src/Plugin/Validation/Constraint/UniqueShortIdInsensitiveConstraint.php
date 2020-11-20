<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if an entity field has a unique value within its bundle.
 *
 * The validation is case insensitive. Works only for RDF entities. This
 * constraint was created explicitly for the RDF entity "Short ID" field.
 *
 * @Constraint(
 *   id = "UniqueShortIdInsensitive",
 *   label = @Translation("Unique case insensitive field within a bundle constraint.", context = "Validation"),
 * )
 */
class UniqueShortIdInsensitiveConstraint extends Constraint {

  /**
   * The message to show when validation fails.
   *
   * @var string
   */
  public $message = 'Content with @field_name %value already exists. Please choose a different @field_name.';

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Drupal\joinup_group\Plugin\Validation\Constraint\UniqueShortIdInsensitiveValidator';
  }

}
