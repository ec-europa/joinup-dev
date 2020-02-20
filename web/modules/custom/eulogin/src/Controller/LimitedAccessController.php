<?php

declare(strict_types = 1);

namespace Drupal\joinup_eulogin\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides a controller for the joinup_eulogin.page.limited_access route.
 */
class LimitedAccessController extends ControllerBase {

  /**
   * Returns the 'Limited access' page content as a render array.
   *
   * @return array
   *   The page content as a render array.
   */
  public function page(): array {
    return [
      [
        '#theme' => 'status_messages',
        '#message_list' => [
          'warning' => [
            $this->t('<p>Dear @name</p><p>Your account access is limited.<br />Starting from 02/03/2020, signing in to Joinup is handled by <a href=":eulogin-url">EU Login</a>, the European Commission Authentication Service. After you sign-in using EU Login, you will be able to synchronise your existing Joinup account to restore your access.</p>', ['@name' => $this->currentUser()->getDisplayName()]),
          ],
        ],
      ],
    ];
  }

}
