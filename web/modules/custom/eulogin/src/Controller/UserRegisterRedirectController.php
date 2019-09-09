<?php

declare(strict_types = 1);

namespace Drupal\joinup_eulogin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Provides route controllers.
 */
class UserRegisterRedirectController extends ControllerBase {

  /**
   * Provides an alternative controller for 'user.register' route.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   The redirect response.
   */
  public function redirectUserRegister(): TrustedRedirectResponse {
    return new TrustedRedirectResponse($this->config('joinup_eulogin.settings')->get('register_url'));
  }

}
