<?php

namespace Drupal\rdf_entity\Entity\Query\Sparql;
use Drupal\Component\Utility\UrlHelper;

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
   *    Inform the user that $uri variable is not a URI.
   */
  public static function uri($uri) {
    if (!UrlHelper::isValid($uri, TRUE)) {
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
