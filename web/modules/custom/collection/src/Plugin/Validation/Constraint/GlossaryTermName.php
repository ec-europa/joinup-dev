<?php

declare(strict_types = 1);

namespace Drupal\collection\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a constraint for the glossary term name.
 *
 * @Constraint(
 *   id = "GlossaryTermName",
 *   label = @Translation("Glossary term name constraint", context = "Validation"),
 * )
 */
class GlossaryTermName extends Constraint {

  /**
   * The violation message for a synonym same as the glossary term name.
   *
   * @var string
   */
  public $message = 'This glossary term (%name) name is already used as synonym of <a href=":url">@glossary</a>. You should remove that synonym before using this name.';

}
