<?php

namespace Drupal\joinup;

use Drupal\flag\Entity\Flag;
use Drupal\node\Entity\NodeType;
use Drupal\simplenews\Entity\Newsletter;

/**
 * Provides custom install tasks.
 */
class JoinupCustomInstallTasks {

  /**
   * Removes some config entities installed by Simplenews module by default.
   *
   * These configuration entities are defined as optional configurations, in the
   * 'config/optional' directory of the Simplenews module, thus they are
   * installed at the end of installation process, after the profile is
   * installed. For this reason we cannot handle this cleanup in the module
   * joinup_newsletter.
   */
  public static function removeSimpleNewsDefaults() {
    // Delete the default newsletter node-type. We use our own.
    NodeType::load('simplenews_issue')->delete();
    // Delete the 'default' newsletter.
    Newsletter::load('default')->delete();
  }

  /**
   * Delete default flags created by the message_subscribe module.
   */
  public static function deleteMessageSubscribeDefaults() {
    $flag_ids = [
      'subscribe_og',
      'subscribe_node',
      'subscribe_term',
      'subscribe_user',
    ];
    foreach ($flag_ids as $flag_id) {
      Flag::load($flag_id)->delete();
    }
  }

}
