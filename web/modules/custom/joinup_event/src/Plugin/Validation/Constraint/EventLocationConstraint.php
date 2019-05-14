<?php

namespace Drupal\joinup_event\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validates the event location field group.
 *
 * @Constraint(
 *   id = "EventLocationConstraint",
 *   label = @Translation("Event location fields constraint", context = "Validation"),
 * )
 */
class EventLocationConstraint extends Constraint {

  public $message = 'At least one location field should be filled in.';

}
