<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\rdf_entity\Entity\Rdf;

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
 * Handle the duplicated solutions in the CTT repository.
 */
function joinup_core_post_update_ctt_duplicates_handle_duplicates() {
  // Create the original duplicates in case they do not exist.
  _joinup_core_post_update_ctt_duplicates_create_duplicates();

  // Merge in the memberships from the solutions into the original entity's
  // list of memberships.
  _joinup_core_post_update_ctt_duplicates_merge_memberships();

  // Merge in solution types.
  _joinup_core_post_update_ctt_duplicates_merge_solution_types();
}

/**
 * Create original entities for CTT.
 */
function _joinup_core_post_update_ctt_duplicates_create_duplicates() {
  // During migration, it might be that among the duplicates, the wrong version
  // was maintained. Create a clone of the duplicate entity to use as the
  // original.
  foreach (_joinup_core_get_duplicated_ids() as $original_id => $duplicate_ids) {
    $group = Rdf::load($original_id);
    // In case the original entity is not found, it means that the entity with
    // the duplicate id was kept after the migration.
    if (!empty($group)) {
      continue;
    }

    foreach ($duplicate_ids as $duplicate_id) {
      if (!($duplicate_group = Rdf::load($duplicate_id))) {
        continue;
      }
      break;
    }

    if (empty($duplicate_group)) {
      return;
    }

    $original_group = clone $duplicate_group;
    $original_group->set('id', $original_id);
    $original_group->enforceIsNew();
    // Unset related distributions so that they are moved later properly.
    $original_group->set('field_is_distribution', NULL);
    $original_group->skip_notification = TRUE;
    $original_group->save();
  }
}

/**
 * Merge memberships in duplicated solutions of ctt.
 */
function _joinup_core_post_update_ctt_duplicates_merge_memberships() {
  // Generate a pseudo function that loads all memberships indexed by uid.
  $memberships_by_uid = function (string $id) {
    $membership_storage = \Drupal::entityTypeManager()->getStorage('og_membership');
    $memberships = $membership_storage->loadByProperties([
      'entity_type' => 'rdf_entity',
      'entity_id' => $id,
    ]);

    foreach ($memberships as $key => $membership) {
      unset($memberships[$key]);
      // Prepend "uid:" to each key to avoid conflicts with membership ids.
      $memberships['uid:' . $membership->getOwnerId()] = $membership;
    }
    return $memberships;
  };

  foreach (_joinup_core_get_duplicated_ids() as $original_id => $duplicate_ids) {
    $group = Rdf::load($original_id);
    // In case the original entity is not found, it means that the entity with
    // the duplicate id was kept after the migration.
    if (empty($group)) {
      continue;
    }

    /** @var \Drupal\og\OgMembershipInterface[] $memberships */
    $memberships = $memberships_by_uid($original_id);
    foreach ($duplicate_ids as $duplicate_id) {
      /** @var \Drupal\og\OgMembershipInterface[] $duplicate_memberships */
      $duplicate_memberships = $memberships_by_uid($duplicate_id);
      foreach ($duplicate_memberships as $key => $duplicate_membership) {
        // If the membership does not exist in the original entity, transfer it
        // and maintain the values.
        if (!isset($memberships[$key])) {
          $duplicate_membership->setGroup($group);
          $duplicate_membership->skip_notification = 1;
          $duplicate_membership->save();
        }
        // If the membership does exist in the original entity, add the roles to
        // the existing membership. We do not need to filter anything out since
        // og is already handling it in ::setRoles.
        // @see \Drupal\og\OgMembershipInterface::getRoles.
        else {
          $duplicate_roles = $duplicate_memberships[$key]->getRoles();
          $original_membership = $memberships[$key];
          $original_membership->setRoles($duplicate_roles);
          $original_membership->skip_notification = 1;
          $original_membership->save();
        }
      }
    }
  }
}

/**
 * Merges in solution types from duplicates towards the original solution.
 */
function _joinup_core_post_update_ctt_duplicates_merge_solution_types() {
  foreach (_joinup_core_get_duplicated_ids() as $original_id => $duplicates_ids) {
    $original_group = Rdf::load($original_id);
    if (empty($original_group)) {
      continue;
    }

    foreach ($duplicates_ids as $duplicate_id) {
      $duplicate_group = Rdf::load($duplicate_id);
      if (empty($duplicate_group)) {
        continue;
      }

      $original_solution_types = !$original_group->get('field_is_solution_type')->isEmpty() ? $original_group->get('field_is_solution_type') : [];
      $duplicate_solution_types = !$duplicate_group->get('field_is_solution_type')->isEmpty() ? $duplicate_group->get('field_is_solution_type') : [];
      $new_types = array_merge($original_solution_types, $duplicate_solution_types);
      if (empty($new_types)) {
        continue;
      }
      $original_group->set('field_is_solution_type', $new_types);
      $original_group->skip_notification;
      $original_group->save();
    }
  }
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
