<?php

namespace Drupal\joinup_discussion\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\joinup_core\Controller\CommunityContentController;
use Drupal\og\OgAccessInterface;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller that handles the form to add discussion to a collection.
 *
 * The parent is passed as a parameter from the route.
 *
 * @package Drupal\joinup_discussion\Controller
 */
class DiscussionController extends CommunityContentController {

  /**
   * {@inheritdoc}
   */
  protected function getBundle() {
    return 'discussion';
  }

}
