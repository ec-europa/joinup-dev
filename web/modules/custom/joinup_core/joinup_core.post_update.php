<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

/**
 * Enable the Sub-Pathauto module.
 */
function joinup_core_post_update_enable_subpathauto() {
  \Drupal::service('module_installer')->install(['subpathauto']);
}

/**
 * Enable the Views Bulk Operations module.
 */
function joinup_core_post_update_install_vbo() {
  \Drupal::service('module_installer')->install(['views_bulk_operations']);
}

/**
 * Enable the Email Registration module.
 */
function joinup_core_post_update_install_email_registration() {
  \Drupal::service('module_installer')->install(['email_registration']);
}

/**
 * Enable the Joinup Invite module.
 */
function joinup_core_post_update_install_joinup_invite() {
  \Drupal::service('module_installer')->install(['joinup_invite']);
}

/**
 * Move the contact form attachments under the private scheme.
 */
function joinup_core_post_update_move_contact_form_attachments() {
  /** @var \Drupal\Core\File\FileSystemInterface $file_system */
  $file_system = Drupal::service('file_system');

  $message_storage = \Drupal::entityTypeManager()->getStorage('message');
  $ids = $message_storage->getQuery()
    ->condition('template', 'contact_form_submission')
    ->exists('field_contact_attachment')
    ->execute();

  foreach ($message_storage->loadMultiple($ids) as $message) {
    /** @var \Drupal\file\FileInterface $attachment */
    if ($attachment = $message->field_contact_attachment->entity) {
      if (!file_exists($attachment->getFileUri())) {
        continue;
      }
      $target = file_uri_target($attachment->getFileUri());
      $uri = "private://$target";
      $destination_dir = $file_system->dirname($uri);
      if (!file_prepare_directory($destination_dir, FILE_CREATE_DIRECTORY)) {
        throw new \RuntimeException("Cannot create directory '$destination_dir'.");
      }
      if (!file_move($attachment, $uri)) {
        throw new \RuntimeException("Cannot move '{$attachment->getFileUri()}' to '$uri'.");
      }
    }
  }

  // Finally, remove the empty public://contact_form directory.
  file_unmanaged_delete_recursive('public://contact_form');
}

/**
 * Enable the Smart Trim module.
 */
function joinup_core_post_update_install_smart_trim() {
  \Drupal::service('module_installer')->install(['smart_trim']);
}

/**
 * Remove stale 'system.action.joinup_transfer_solution_ownership' config.
 */
function joinup_core_post_update_remove_action_transfer_solution_ownership() {
  \Drupal::configFactory()
    ->getEditable('system.action.joinup_transfer_solution_ownership')
    ->delete();
}

/**
 * Enable the Tallinn module.
 */
function joinup_core_post_update_install_tallinn() {
  \Drupal::service('module_installer')->install(['tallinn']);
}

/**
 * Retrieves a list of entities with their duplicated ids.
 *
 * @return array
 *   An associative array of ids where each entry is an array of ids of
 *   duplicates.
 */
function _joinup_core_get_duplicated_ids(): array {
  return [
    'http://administracionelectronica.gob.es/ctt/dscp' => [
      'http://administracionelectronica.gob.es/ctt/dscp_1',
    ],
    'http://administracionelectronica.gob.es/ctt/pau' => [
      'http://administracionelectronica.gob.es/ctt/pau_1',
    ],
    'http://administracionelectronica.gob.es/ctt/pfiaragon' => [
      'http://administracionelectronica.gob.es/ctt/pfiaragon_1',
    ],
    'http://administracionelectronica.gob.es/ctt/dir3' => [
      'http://administracionelectronica.gob.es/ctt/dir3_1',
    ],
    'http://administracionelectronica.gob.es/ctt/svd' => [
      'http://administracionelectronica.gob.es/ctt/svd_1',
    ],
    'http://administracionelectronica.gob.es/ctt/scsp' => [
      'http://administracionelectronica.gob.es/ctt/scsp_1',
    ],
    'http://administracionelectronica.gob.es/ctt/tsa' => [
      'http://administracionelectronica.gob.es/ctt/tsa_1',
    ],
    'http://administracionelectronica.gob.es/ctt/afirma' => [
      'http://administracionelectronica.gob.es/ctt/afirma_1',
      'http://administracionelectronica.gob.es/ctt/afirma_2',
    ],
    'http://administracionelectronica.gob.es/ctt/codice' => [
      'http://administracionelectronica.gob.es/ctt/codice_1',
    ],
    'http://administracionelectronica.gob.es/ctt/badaral' => [
      'http://administracionelectronica.gob.es/ctt/badaral_1',
    ],
    'http://administracionelectronica.gob.es/ctt/sicres' => [
      'http://administracionelectronica.gob.es/ctt/sicres',
    ],
    'http://administracionelectronica.gob.es/ctt/pruebalola' => [
      'http://administracionelectronica.gob.es/ctt/pruebalola_1',
    ],
    'http://administracionelectronica.gob.es/ctt/alpadron' => [
      'http://administracionelectronica.gob.es/ctt/alpadron_1',
    ],
    'http://administracionelectronica.gob.es/ctt/avanzalocalgis' => [
      'http://administracionelectronica.gob.es/ctt/avanzalocalgis_1',
    ],
  ];
}
