<?php

/**
 * @file
 * Contains collection.page.inc..
 *
 * Page callback for Collection entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Collection templates.
 *
 * Default template: collection.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_collection(array &$variables) {
  // Fetch Collection Entity Object.
  $collection = $variables['elements']['#collection'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
