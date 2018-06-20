<?php

namespace Drupal\joinup_event\Controller;

use Drupal\joinup_core\Controller\CommunityContentController;

/**
 * Controller that handles the form to add event to a collection.
 *
 * The parent is passed as a parameter from the route.
 */
class EventController extends CommunityContentController {

  /**
   * {@inheritdoc}
   */
  protected function getBundle() {
    return 'event';
  }

}
