<?php

declare(strict_types = 1);

namespace Drupal\joinup_document\Controller;

use Drupal\joinup_core\Controller\CommunityContentController;

/**
 * Controller that handles the form to add document to a collection.
 *
 * The parent is passed as a parameter from the route.
 */
class DocumentController extends CommunityContentController {

  /**
   * {@inheritdoc}
   */
  protected function getBundle(): string {
    return 'document';
  }

}
