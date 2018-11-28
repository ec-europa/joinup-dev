<?php

namespace Drupal\rdf_serialization\Encoder;

use EasyRdf\Format;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

/**
 * Adds RDF encoder support for the Serialization API.
 */
class RdfEncoder implements EncoderInterface {

  /**
   * The formats that this encoder supports.
   *
   * @var array
   */
  protected static $supportedFormats = [
    'jsonld',
    'rdfxml',
    'ntriples',
    'turtle',
    'n3',
  ];

  /**
   * {@inheritdoc}
   */
  public function supportsEncoding($format) {
    return in_array($format, static::supportedFormats());
  }

  /**
   * {@inheritdoc}
   *
   * Uses HTML-safe strings, with several characters escaped.
   */
  public function encode($data, $format, array $context = []) {
    if (isset($data['_rdf_entity'])) {
      return $data['_rdf_entity'];
    }
    return NULL;
  }

  /**
   * Build a list of supported formats.
   *
   * @return \EasyRdf\Format[]
   *   List of supported formats.
   */
  public static function supportedFormats(): array {
    $formats = Format::getFormats();
    /** @var \EasyRdf\Format[] $supported_formats */
    return array_intersect($formats, static::$supportedFormats);
  }

}
