<?php

/**
 * @file
 * Contains \DiscussionSubContext.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\NodeTrait;
use Drupal\message\Entity\Message;

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

  /**
   * Checks that the given discussion has an expected number of subscribers.
   *
   * @param string $title
   *   The discussion title.
   * @param int $count
   *   The expected number of subscribers.
   *
   * @Then the :title discussion should have :count subscriber(s)
   */
  public function assertSubscribers($title, $count) {
    $discussion = $this->getNodeByTitle($title, 'discussion');
    $subscribers = \Drupal::service('message_subscribe.subscribers')->getSubscribers($discussion, new Message([], 'message'));
    PHPUnit_Framework_Assert::assertEquals($count, count($subscribers));
  }

}
