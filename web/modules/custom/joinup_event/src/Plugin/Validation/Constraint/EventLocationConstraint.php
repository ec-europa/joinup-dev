<?php

declare(strict_types = 1);

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

  /**
   * The message to show if validation fails.
   *
   * @var string
   */
  public $message = 'At least one location field should be filled in.';

}
