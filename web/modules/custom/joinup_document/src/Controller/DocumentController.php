<?php

namespace Drupal\joinup_document\Controller;

use Drupal\joinup_core\Controller\CommunityContentController;

/**
 * Controller that handles the form to add document to a collection.
 *
 * The parent is passed as a parameter from the route.
 *
 * @package Drupal\joinup_document\Controller
 */
class DocumentController extends CommunityContentController {

  /**
   * {@inheritdoc}
   */
  protected function getBundle() {
    return 'document';
  }

}
