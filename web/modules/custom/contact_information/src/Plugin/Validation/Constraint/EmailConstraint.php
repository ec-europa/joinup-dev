<?php

declare(strict_types = 1);

namespace Drupal\contact_information\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if an email is valid according Joinup specifications.
 *
 * Do not use this for anything other than contact information emails. For all
 * other emails use the standard EmailValidator.
 *
 * @see \Drupal\Component\Utility\EmailValidator
 *
 * @Constraint(
 *   id = "JoinupEmail",
 *   label = @Translation("Valid e-mail per Joinup specifications", context = "Validation"),
 * )
 */
class EmailConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'The e-mail %email is not valid.';

}
