<?php

namespace Drupal\rdf_entity;

/**
 * Handler class for the rdf uris.
 *
 * @package Drupal\rdf_entity
 */
class UriEncoder {

  /**
   * Encodes the url in a user friendly way.
   *
   * @param string $uri
   *   The uri to be encoded.
   *
   * @return string
   *   The encoded uri.
   */
  public static function encodeUrl($uri) {
    $encode_chars = self::charsToEscape();
    $keys = array_keys($encode_chars);
    $replace = array_values($encode_chars);
    $uri = str_replace('_', '_a', $uri);
    return str_replace($keys, $replace, $uri);
  }

  /**
   * Decodes a uri using the characters defined in this class.
   *
   * @param string $uri
   *   The uri to be decoded.
   *
   * @return string
   *   The decoded uri.
   */
  public static function decodeUrl($uri) {
    $encode_chars = self::charsToEscape();
    $keys = array_keys($encode_chars);
    $replace = array_values($encode_chars);
    $uri = str_replace($replace, $keys, $uri);
    return str_replace('_a', '_', $uri);
  }

  /**
   * A list of characters to be replaced in a Uri.
   *
   * The characters that are escaped are the valid characters allowed in a Uri.
   *
   * @return array
   *   The array of characters where the index is the character to be encoded
   *    and the value is the replacement.
   *
   * @see: https://tools.ietf.org/html/rfc3986#section-2
   */
  protected static function charsToEscape() {
    return [
      '-' => '_b',
      '.' => '_c',
      '~' => '_d',
      ':' => '_e',
      '/' => '_f',
      '?' => '_g',
      '#' => '_h',
      '[' => '_i',
      ']' => '_j',
      '@' => '_k',
      '!' => '_l',
      '$' => '_m',
      '&' => '_n',
      '\'' => '_o',
      '(' => '_p',
      ')' => '_q',
      '*' => '_r',
      '+' => '_s',
      ',' => '_t',
      ';' => '_u',
      '=' => '_v',
      '`' => '_w',
    ];
  }

}
