<?php

declare(strict_types = 1);

namespace Drupal\joinup_news\Controller;

use Drupal\joinup_community_content\Controller\CommunityContentController;

/**
 * Controller that handles the form to add news to a collection or a solution.
 *
 * The parent is passed as a parameter from the route.
 */
class NewsController extends CommunityContentController {

  /**
   * {@inheritdoc}
   */
  protected function getBundle(): string {
    return 'news';
  }

}
