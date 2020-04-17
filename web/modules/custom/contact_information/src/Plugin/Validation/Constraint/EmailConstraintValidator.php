<?php

declare(strict_types = 1);

namespace Drupal\contact_information\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates email using a Joinup business analyst approved regular expression.
 *
 * Do not use this for anything other than contact information emails. For all
 * other emails use the standard EmailValidator.
 *
 * @see \Drupal\Component\Utility\EmailValidator
 */
class EmailConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (!$item = $items->first()) {
      return;
    }

    // The regular expression seems to originate from this online resource.
    // @see https://www.regular-expressions.info/email.html
    if (!preg_match('/^[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/i', $item->value)) {
      $this->context->addViolation($constraint->message, ['%email' => $item->value]);
    }
  }

}
