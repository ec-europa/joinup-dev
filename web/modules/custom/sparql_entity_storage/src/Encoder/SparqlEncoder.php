<?php

declare(strict_types = 1);

namespace Drupal\sparql_entity_storage\Encoder;

use Drupal\sparql_entity_storage\SparqlEncoderInterface;
use EasyRdf\Format;

/**
 * Adds RDF encoder support for the Serialization API.
 */
class SparqlEncoder implements SparqlEncoderInterface {

  /**
   * Memory cache for supported formats.
   *
   * @var \EasyRdf\Serialiser[]
   */
  protected static $supportedFormats;

  /**
   * {@inheritdoc}
   */
  public function supportsEncoding($format): bool {
    return !empty(static::getSupportedFormats()[$format]);
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data, $format, array $context = []) {
    if (isset($data['_sparql_entity'])) {
      return $data['_sparql_entity'];
    }
    // This is an unsupported format. Show the error message.
    if (count($data) === 1 && isset($data['message'])) {
      return $data['message'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSupportedFormats(): array {
    if (!isset(static::$supportedFormats)) {
      $container_registered_formats = \Drupal::getContainer()->getParameter('sparql_entity.encoders');
      $rdf_serializers = Format::getFormats();
      static::$supportedFormats = array_intersect_key($rdf_serializers, $container_registered_formats);
    }
    return static::$supportedFormats;
  }

}
