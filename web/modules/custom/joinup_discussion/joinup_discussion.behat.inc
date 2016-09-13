<?php

/**
 * @file
 * Contains \DiscussionSubContext.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\NodeTrait;

/**
 * Behat step definitions for testing discussions.
 */
class DiscussionSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use NodeTrait;

  /**
   * Navigates to the canonical page display of a discussion.
   *
   * @param string $title
   *   The name of the discussion.
   *
   * @When (I )go to the :title discussion
   * @When (I )visit the :title discussion
   */
  public function visitDiscussion($title) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->getNodeByTitle($title, 'discussion');
    $this->visitPath($node->url());
  }

}
