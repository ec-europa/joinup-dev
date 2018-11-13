<?php

namespace Drupal\rdf_serialization\Normalizer;

use Drupal\rdf_serialization\Encoder\RdfEncoder;
use Drupal\serialization\Normalizer\NormalizerBase as SerializationNormalizerBase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Base class for Normalizers.
 */
abstract class NormalizerBase extends SerializationNormalizerBase implements DenormalizerInterface {

  /**
   * {@inheritdoc}
   */
  protected function checkFormat($format = NULL) {
    return in_array($format, RdfEncoder::supportedFormats());
  }

}
