<?php

declare(strict_types = 1);

namespace Drupal\joinup_rdf_graph\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks the validity of an 'rdf_graph' RDF entity label.
 *
 * @Constraint(
 *   id = "RdfGraphLabel",
 *   label = @Translation("Valid 'rdf_graph' RDF entity label", context = "Validation"),
 * )
 */
class RdfGraphLabel extends Constraint {

  /**
   * The message to show when the validation fails with reserved word.
   *
   * @var string
   */
  public $messageReservedLabel = "Cannot use '%value' as title, as it's a reserved word. Please choose a different title.";

  /**
   * The message to show when the validation fails with title duplication.
   *
   * @var string
   */
  public $messageUniqueLabel = "An RDF graph titled '%value' already exists. Please choose a different title.";

}
