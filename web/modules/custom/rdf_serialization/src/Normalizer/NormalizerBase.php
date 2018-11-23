<?php

declare(strict_types = 1);

namespace Drupal\rdf_serialization\Normalizer;

use Drupal\rdf_serialization\Encoder\RdfEncoder;
use Drupal\serialization\Normalizer\NormalizerBase as SerializationNormalizerBase;

/**
 * Base class for Normalizers.
 */
abstract class NormalizerBase extends SerializationNormalizerBase {

  /**
   * {@inheritdoc}
   */
  protected function checkFormat($format = NULL) {
    return in_array($format, RdfEncoder::supportedFormats());
  }

}
