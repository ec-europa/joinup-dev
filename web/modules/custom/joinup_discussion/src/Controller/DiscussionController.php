<?php

declare(strict_types = 1);

namespace Drupal\joinup_discussion\Controller;

use Drupal\joinup_community_content\Controller\CommunityContentController;

/**
 * Controller that handles the form to add discussion to a collection.
 *
 * The parent is passed as a parameter from the route.
 */
class DiscussionController extends CommunityContentController {

  /**
   * {@inheritdoc}
   */
  protected function getBundle(): string {
    return 'discussion';
  }

}
