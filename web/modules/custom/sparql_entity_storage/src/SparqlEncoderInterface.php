<?php

declare(strict_types = 1);

namespace Drupal\sparql_entity_storage;

use Symfony\Component\Serializer\Encoder\EncoderInterface;

/**
 * Provides an interface to SPARQL encoders.
 */
interface SparqlEncoderInterface extends EncoderInterface {

  /**
   * Builds a list of supported formats.
   *
   * @return \EasyRdf\Serialiser[]
   *   List of supported formats.
   */
  public static function getSupportedFormats(): array;

}
