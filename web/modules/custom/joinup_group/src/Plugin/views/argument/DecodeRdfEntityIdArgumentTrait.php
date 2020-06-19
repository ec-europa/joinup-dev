<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Plugin\views\argument;

use Drupal\sparql_entity_storage\UriEncoder;

/**
 * Reusable Views argument setter for arguments based on Sparql URIs.
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
