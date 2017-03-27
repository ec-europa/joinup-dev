<?php

namespace Drupal\rdf_entity\Entity\Query\Sparql;

use Drupal\Component\Utility\UrlHelper;
use EasyRdf\Serialiser\Ntriples;

/**
 * Class SparqlArg.
 *
 * Wrap Sparql arguments. This provides a central point for escaping.
 *
 * @todo Return SparqlArgument objects in order to distinguish between
 * raw strings and sanitized ones. Query should expect objects.
 *
 * @package Drupal\rdf_entity\Entity\Query\Sparql
 */
class SparqlArg {

  /**
   * URI Query argument.
   *
   * @param array $uris
   *   An array of URIs to serialize.
   *
   * @return string
   *   Sparql serialized URIs.
   */
  public static function serializeUris(array $uris) {
    foreach ($uris as $index => $uri) {
      $uris[$index] = self::uri($uri);
    }
    return implode(', ', $uris);
  }

  /**
   * URI Query argument.
   *
   * @param string $uri
   *   A valid URI to use as a query parameter.
   *
   * @return string
   *   Sparql validated URI.
   */
  public static function uri($uri) {
    // If the uri is already encapsulated with the '<>' symbols, remove these
    // and re-serialize the uri.
    if (preg_match('/^<(.+)>$/', $uri) !== NULL) {
      $uri = trim($uri, '<>');
    }
    return self::serialize($uri, 'uri');
  }

  /**
   * URI Query argument.
   *
   * @param string $uri
   *   A string to be checked.
   *
   * @return bool
   *   Whether it is a valid SPARQL URI or not. The URI is a valid URI whether
   *   or not it is encapsulated with '<>'.
   */
  public static function isValidResource($uri) {
    return UrlHelper::isValid(trim($uri, '<>'), TRUE);
  }

  /**
   * Literal Query argument.
   *
   * @param string $value
   *   An unescaped text string to use as a Sparql query.
   *
   * @return string
   *   Sparql escaped string literal.
   */
  public static function literal($value) {
    return self::serialize($value, 'literal');
  }

  /**
   * Returns a serialized version of the given value of the given format.
   *
   * @param string $value
   *   The value to be serialized.
   * @param string $format
   *   One of the formats used in \EasyRdf\Serialiser\Ntriples::serializeValue.
   *
   * @return string
   *   The outcome of the serialization.
   */
  public static function serialize($value, $format) {
    $serializer = new Ntriples();
    return $serializer->serialiseValue([
      'value' => $value,
      'type' => $format,
    ]);
  }

}
