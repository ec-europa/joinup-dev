<?php

/**
 * @file
 * Post update functions for Tallinn.
 */

/**
 * Install the 'embed_block' module and add the dashboard block in page content.
 */
function tallinn_post_update_add_block() {
  \Drupal::service('module_installer')->install(['embed_block']);

  /** @var \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository */
  $entity_repository = \Drupal::service('entity.repository');
  /** @var \Drupal\node\NodeInterface $custom_page */
  $custom_page = $entity_repository->loadEntityByUuid('node', '9d7b6405-061a-4064-ae7e-b34c67f3afad');
  $body_field = $custom_page->get('body');
  $value = $body_field->getValue();
  $value[0]['value'] .= '{block:tallinn_dashboard}';
  $body_field->setValue($value);
  $custom_page->save();
}
