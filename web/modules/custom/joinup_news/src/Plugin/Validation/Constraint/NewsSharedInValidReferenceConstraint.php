<?php

namespace Drupal\joinup_news\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validates the "Shared in" field for the news node bundle.
 *
 * @Constraint(
 *   id = "NewsSharedInValidReference",
 *   label = @Translation("News 'Shared in' reference constraint", context = "Validation"),
 *   type = { "entity_reference" }
 * )
 */
class NewsSharedInValidReferenceConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'You cannot reference %label in field %field_name.';

}
