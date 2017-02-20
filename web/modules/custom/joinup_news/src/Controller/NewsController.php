<?php

namespace Drupal\joinup_news\Controller;

use Drupal\joinup_core\Controller\CommunityContentController;

/**
 * Controller that handles the form to add news to a collection or a solution.
 *
 * The parent is passed as a parameter from the route.
 *
 * @package Drupal\joinup_news\Controller
 */
class NewsController extends CommunityContentController {

  /**
   * {@inheritdoc}
   */
  protected function getBundle() {
    return 'news';
  }

}
