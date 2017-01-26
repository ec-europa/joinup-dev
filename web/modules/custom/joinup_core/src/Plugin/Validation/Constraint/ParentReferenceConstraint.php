<?php

namespace Drupal\joinup_core\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that a field is not making a circular reference to the parent.
 *
 * @Constraint(
 *   id = "ParentReference",
 *   label = @Translation("Parent reference constraint", context = "Validation"),
 *   type = { "entity_reference" }
 * )
 */
class ParentReferenceConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'You cannot reference the parent %parent in field %field_name.';

  /**
   * The method to call to retrieve the parent of the entity.
   *
   * @var string
   */
  public $method;

  /**
   * The name of the service to be used to retrieve the parent of the entity.
   *
   * @var string
   */
  public $service;

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions() {
    return ['service', 'method'];
  }

}
