<?php

namespace Drupal\rdf_entity\Entity\Query\Sparql;

use Drupal\Component\Utility\UrlHelper;
use Drupal\rdf_entity\RdfFieldHandler;
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
   * @param string $delimiter
   *   The delimiter to use.
   *
   * @return string
   *   Sparql serialized URIs.
   */
  public static function serializeUris(array $uris, $delimiter = ', ') {
    return implode($delimiter, self::toResourceUris($uris));
  }

  /**
   * URI Query arguments.
   *
   * @param array $uris
   *   An array of URIs to serialize.
   *
   * @return array
   *   The encoded uris.
   */
  public static function toResourceUris(array $uris) {
    foreach ($uris as $index => $uri) {
      $uris[$index] = self::uri($uri);
    }
    return $uris;
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
    return self::serialize($uri, RdfFieldHandler::RESOURCE);
  }

  /**
   * URI Query argument.
   *
   * @param string $uri
   *   A string to be checked.
   *
   * @return bool
   *   Whether it is a valid RDF resource or not. The URI is a valid URI whether
   *   or not it is encapsulated with '<>'.
   */
  public static function isValidResource($uri) {
    return UrlHelper::isValid(trim($uri, '<>'), TRUE);
  }

  /**
   * URI Query argument.
   *
   * @param array $uris
   *   An array string to be checked.
   *
   * @return bool
   *   Whether the items in the array are valid RDF resource or not. The URI is
   *   a valid URI whether or not it is encapsulated with '<>'. If at least one
   *   URI is not a valid resource, FALSE will be returned.
   */
  public static function isValidResources(array $uris) {
    foreach ($uris as $uri) {
      if (!SparqlArg::isValidResource($uri)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Returns a serialized version of the given value of the given format.
   *
   * @param string $value
   *   The value to be serialized.
   * @param string $format
   *   One of the formats defined in
   *   \Drupal\rdf_entity\RdfFieldHandler::getSupportedDatatypes().
   * @param string $lang
   *   The lang code.
   *
   * @return string
   *   The outcome of the serialization.
   */
  public static function serialize($value, $format, $lang = NULL) {
    $data['value'] = $value;
    switch ($format) {
      case RdfFieldHandler::RESOURCE:
        $data['type'] = 'uri';
        break;

      case RdfFieldHandler::NON_TYPE:
        $data['type'] = 'literal';
        break;

      case RdfFieldHandler::TRANSLATABLE_LITERAL:
        $data['lang'] = $lang;
        $data['type'] = 'literal';
        break;

      default:
        $data['type'] = 'literal';
        $data['datatype'] = $format;

    }
    $serializer = new Ntriples();
    return $serializer->serialiseValue($data);
  }

}
