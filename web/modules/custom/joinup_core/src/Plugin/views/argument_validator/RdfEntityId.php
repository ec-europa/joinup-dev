<?php

namespace Drupal\joinup_core\Plugin\views\argument_validator;

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
  public function validateArgument($argument) {
    $argument = UriEncoder::decodeUrl($argument);
    return parent::validateArgument($argument);
  }

}
