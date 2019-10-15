<?php

namespace Drupal\joinup_user\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;

/**
 * A menu link to the current user edit page.
 */
class UserEditMenuLink extends MenuLinkDefault {

  /**
   * {@inheritdoc}
   *
   * Add current user id as dynamic route parameter.
   */
  public function getRouteParameters() {
    return [
      'user' => \Drupal::currentUser()->id(),
    ];
  }

}
