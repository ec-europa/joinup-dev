<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Page controller for the member permissions information table.
 *
 * This table is shown in a modal dialog when pressing the "Member permissions"
 * link in the Members page.
 */
class GroupMembershipPermissionsInformationController extends ControllerBase {

  /**
   * Builds the table that shows information about member permissions.
   *
   * @return array
   *   A render array containing the table.
   */
  public function build(): array {
    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('The dragon swooped once more lower than ever, and as he turned and dived down his belly glittered white with sparkling fires of gems in the moon.'),
    ];

    return $build;
  }

}
