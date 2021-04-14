<?php

declare(strict_types = 1);

namespace Drupal\sparql_entity_storage\Normalizer;

use Drupal\sparql_entity_storage\Encoder\SparqlEncoder;
use Drupal\serialization\Normalizer\NormalizerBase as SerializationNormalizerBase;

/**
 * Base class for Normalizers.
 */
abstract class NormalizerBase extends SerializationNormalizerBase {

  /**
   * {@inheritdoc}
   */
  protected function checkFormat($format = NULL): bool {
    return !empty(SparqlEncoder::getSupportedFormats()[$format]);
  }

}
