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
 * Migrate from Piwik to Matomo.
 */
function joinup_core_post_update_install_piwik2matomo() {
  /** @var \Drupal\Core\Extension\ModuleInstallerInterface $installer */
  $installer = \Drupal::service('module_installer');
  // Install the new modules. This is also uninstalling 'piwik_reporting_api'.
  $installer->install(['matomo_reporting_api']);
  // Uninstall the Piwik module.
  $installer->uninstall(['piwik']);
  // Note that the module installer API requires the presence of the modules in
  // the codebase. For this reason they will be removed from the codebase in a
  // follow-up.
}

/**
 * Enable 'spain_ctt' module.
 */
function joinup_core_post_update_install_spain_ctt() {
  \Drupal::service('module_installer')->install(['spain_ctt']);
}

/**
 * Add the user support menu.
 */
function joinup_core_post_update_remove_tour_buttons() {
  \Drupal::service('module_installer')->install(['menu_admin_per_menu']);
  $config_factory = \Drupal::configFactory();
  $config_factory->getEditable('block.block.tourbutton_2')->delete();
  $config_factory->getEditable('block.block.tourbutton')->delete();
}

/**
 * Enable 'rdf_serialization' module.
 */
function joinup_core_post_update_install_rdf_serialization() {
  \Drupal::service('module_installer')->install(['rdf_serialization']);
}
