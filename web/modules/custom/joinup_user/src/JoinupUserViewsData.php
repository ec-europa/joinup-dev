<?php

declare(strict_types = 1);

namespace Drupal\joinup_user;

use Drupal\user\UserViewsData;

/**
 * Provides the views data for the Joinup user entity type.
 */
class JoinupUserViewsData extends UserViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['users_field_data']['status']['field'] = [
      'id' => 'field',
      'default_formatter' => 'joinp_user_account_status',
      'field_name' => 'status',
    ];
    $data['users_field_data']['status']['filter'] = [
      'id' => 'user_tristate_status',
    ];

    return $data;
  }

}
