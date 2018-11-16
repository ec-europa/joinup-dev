<?php

namespace Drupal\rdf_serialization\Encoder;

use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use SplTempFileObject;
use Drupal\Component\Utility\Html;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;

/**
 * Adds RDF encoder support for the Serialization API.
 */
class RdfEncoder implements EncoderInterface, DecoderInterface {

  /**
   * The format that this encoder supports.
   *
   * @var string
   */
  protected static $format = 'rdfxml';

  /**
   * {@inheritdoc}
   */
  public function supportsEncoding($format) {
    return $format == static::$format;
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
    return var_export($data, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function decode($data, $format, array $context = array()) {

  }

}
