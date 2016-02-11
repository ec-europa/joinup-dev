<?php
/**
 * @file
 * Wrapper for arguments in Sparql condition.
 */

namespace Drupal\rdf_entity\Entity\Query\Sparql;

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
   * @param string $uri
   *    A valid URI to use as a query parameter.
   *
   * @return string
   *    Sparql validated URI.
   *
   * @throws \Exception
   */
  public static function uri($uri) {
    if (!filter_var($uri, FILTER_VALIDATE_URL)) {
      throw new \Exception('Provided value is not a URI.');
    }
    return '<' . $uri . '>';
  }

  /**
   * Literal Query argument.
   *
   * @param string $value
   *    An unescaped text string to use as a Sparql query.
   *
   * @return string
   *    Sparql escaped string literal.
   */
  public static function literal($value) {
    // @todo Support all xml data types, as well as language extensions.
    $matches = 1;
    while ($matches) {
      $matches = 0;
      $value = str_replace('"""', '', $value, $matches);
    }

    return '"""' . $value . '"""';
  }

}
