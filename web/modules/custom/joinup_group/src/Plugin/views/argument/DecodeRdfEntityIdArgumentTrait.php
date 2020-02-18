<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Plugin\views\argument;

use Drupal\sparql_entity_storage\UriEncoder;

/**
 * Reusable code for group argument views handlers.
 */
trait DecodeRdfEntityIdArgumentTrait {

  /**
   * {@inheritdoc}
   */
  public function setArgument($arg): bool {
    $arg = UriEncoder::decodeUrl($arg);
    return parent::setArgument($arg);
  }

}
