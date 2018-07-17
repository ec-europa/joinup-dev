<?php

/**
 * @file
 * Post update functions for Tallinn.
 */

use Drupal\node\Entity\Node;

/**
 * Install the 'embed_block' module and add the dashboard block in page content.
 */
function tallinn_post_update_add_block() {
  \Drupal::service('module_installer')->install(['embed_block']);

  // The 'Implementation monitoring' page node ID equals 701254.
  /** @var \Drupal\node\NodeInterface $custom_page */
  $custom_page = Node::load(701254);
  $body_field = $custom_page->get('body');
  $value = $body_field->getValue();
  $value[0]['value'] .= '{block:tallinn_dashboard}';
  $body_field->setValue($value);
  $custom_page->save();
}
