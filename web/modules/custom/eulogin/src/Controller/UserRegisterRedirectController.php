<?php

declare(strict_types = 1);

namespace Drupal\joinup_eulogin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
    $register_url = $this->config('joinup_eulogin.settings')->get('register_url');
    if ($register_url) {
      return new TrustedRedirectResponse($register_url);
    }
    throw new NotFoundHttpException();
  }

}
