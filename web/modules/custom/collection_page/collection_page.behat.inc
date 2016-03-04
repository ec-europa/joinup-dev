<?php

/**
 * @file
 * Contains \CollectionPageSubContext.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;

/**
 * Behat step definitions for testing collection pages.
 */
class CollectionPageSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {
  /**
   * Test collections.
   *
   * @var \Drupal\node\Entity\Node[]
   */
  protected $collectionPages = [];

  /**
   * The bundle type of the content entity.
   *
   * @var string
   */
  protected $bundle = 'collection_page';

}
