<?php

namespace Drupal\joinup_migrate;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;

/**
 * Helper methods for HTML cleanup.
 */
class HtmlUtility {

  /**
   * Supported tags in HTML to plain text conversion.
   *
   * @var string[]
   */
  protected static $supportedTags = [
    'br',
    'p',
    'div',
    'pre',
    'blockquote',
    'ul',
    'ol',
    'li',
    'dl',
    'dt',
    'dd',
    'h1',
    'h2',
    'h3',
    'h4',
    'h5',
    'h6',
  ];

  /**
   * Transforms an HTML string into plain text.
   *
   * Inspired from \Drupal\Core\Mail\MailFormatHelper::htmlToText().
   *
   * @param string $html
   *   The HTML markup to be converted.
   *
   * @return string
   *   The plain text version.
   *
   * @see \Drupal\Core\Mail\MailFormatHelper::htmlToText()
   */
  public static function htmlToPlainText($html) {
    // Ensure tags, entities, attributes are well-formed and properly nested.
    $html = Html::normalize(Xss::filter($html, static::$supportedTags));

    // Split tags from text.
    $split = preg_split('/<([^>]+?)>/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

    // Note: PHP ensures the array consists of alternating delimiters and
    // literals and begins and ends with a literal (inserting NULL as required).
    // Odd/even counter (tag or no tag).
    $is_tag = FALSE;
    $output = '';

    foreach ($split as $value) {
      // Holds a string ready to be formatted and output.
      $chunk = NULL;

      // Process HTML tags (but don't output any literally).
      if ($is_tag) {
        list($tagname) = explode(' ', strtolower($value), 2);
        if (preg_match('@^\/[blockquote|dd|dl|li|ul|ul|h1|h2|h3|h4|h5|h6|div|pre|p]@i', $tagname)) {
          // Ensure blank new-line.
          $chunk = '';
        }
      }
      // Process blocks of text.
      else {
        $value = trim(Html::decodeEntities($value));
        // Non-breaking space to normal space.
        $value = str_replace(chr(194) . chr(160), ' ', $value);
        if (Unicode::strlen($value)) {
          $chunk = $value;
        }
      }

      // See if there is something waiting to be output.
      if (isset($chunk)) {
        // Append a newline char.
        $output .= "$chunk\n";
      }

      $is_tag = !$is_tag;
    }

    // Unify line-endings to Unix style. DOS to Unix.
    $output = str_replace("\r\n", "\n", $output);
    // Old Mac OS to Unix.
    $output = str_replace("\r", "\n", $output);

    // Cleanup line trailing spaces.
    $output = implode("\n", array_map('trim', explode("\n", trim($output))));

    // Allow two consecutive line breaks for paragraphs but no more.
    $output = preg_replace('@\n{3,}@', "\n\n", $output);

    return $output;
  }

}
