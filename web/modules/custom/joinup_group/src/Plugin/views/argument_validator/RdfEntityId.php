<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Plugin\views\argument_validator;

use Drupal\sparql_entity_storage\UriEncoder;
use Drupal\views\Plugin\views\argument_validator\Entity;

/**
 * Validates whether a RDF entity encoded ID is valid argument.
 *
 * @ViewsArgumentValidator(
 *   id = "entity:rdf_entity",
 *   title = @Translation("RDF entity encoded ID"),
 * )
 */
class RdfEntityId extends Entity {

  /**
   * {@inheritdoc}
   */
  public function validateArgument($argument): bool {
    $argument = UriEncoder::decodeUrl($argument);
    return parent::validateArgument($argument);
  }

}
