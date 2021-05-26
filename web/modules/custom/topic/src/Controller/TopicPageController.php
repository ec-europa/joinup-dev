<?php

namespace Drupal\topic\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Topic routes.
 */
class TopicPageController extends ControllerBase {

  /**
   * Builds the response.
   *
   * WARNING: The user will never be able to access this page as they will be
   * redirected to the search page.
   *
   */
  public function build() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('Nothing to see here. Have a nice day :)!'),
    ];

    return $build;
  }

}
