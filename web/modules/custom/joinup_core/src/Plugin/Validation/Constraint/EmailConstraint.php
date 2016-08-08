<?php

namespace Drupal\joinup_core\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if an email is valid according Joinup specifications.
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

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return parent::validatedBy();
  }

}
