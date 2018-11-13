<?php

namespace Drupal\rdf_serialization\Encoder;

use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

/**
 * Adds RDF encoder support for the Serialization API.
 */
class RdfEncoder implements EncoderInterface, DecoderInterface {

  /**
   * The format that this encoder supports.
   *
   * @var array
   */
  protected static $formats = ['rdfxml'];

  /**
   * {@inheritdoc}
   */
  public function supportsEncoding($format) {
    return in_array($format, static::$formats);
  }

  /**
   * {@inheritdoc}
   */
  public function supportsDecoding($format) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * Uses HTML-safe strings, with several characters escaped.
   */
  public function encode($data, $format, array $context = array()) {
    if (isset($data['_rdf_entity'])) {
      return $data['_rdf_entity'];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function decode($data, $format, array $context = array()) {

  }

  public static function supportedFormats() {
    return self::$formats;
  }

}
