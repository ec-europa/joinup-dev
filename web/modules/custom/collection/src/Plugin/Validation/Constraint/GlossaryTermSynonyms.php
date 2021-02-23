<?php

declare(strict_types = 1);

namespace Drupal\collection\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides a constraint for the glossary term synonyms.
 *
 * @Constraint(
 *   id = "GlossaryTermSynonyms",
 *   label = @Translation("Glossary term synonyms constraint", context = "Validation"),
 * )
 */
class GlossaryTermSynonyms extends Constraint {

  /**
   * The violation message for a synonym same as the glossary term name.
   *
   * @var string
   */
  public $messageSameAsTermName = "A synonym cannot be the same as the glossary term name (%name).";

  /**
   * The violation message for duplicate synonyms.
   *
   * @var string
   */
  public $messageDuplicate = "The '%synonym' synonym is duplicated. Keep only one entry.";

  /**
   * The violation message for synonyms already in other glossary terms.
   *
   * @var string
   */
  public $messageInOtherTerms = 'Some synonyms are already used in other glossary terms either as term name or as term synonyms: ';

}
