<?php

/**
 * @file
 * Contains licence.page.inc.
 *
 * Page callback for Licence entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Licence templates.
 *
 * Default template: licence.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_licence(array &$variables) {
  // Fetch Licence Entity Object.
  $licence = $variables['elements']['#licence'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
