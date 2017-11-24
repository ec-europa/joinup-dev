<?php

/**
 * @file
 * Contains \DiscussionSubContext.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\NodeTrait;
use Drupal\joinup_subscription\JoinupSubscriptionInterface;

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
    $subscribers = $this->getSubscriptionService()->getSubscribers($discussion, 'subscribe_discussions');
    PHPUnit_Framework_Assert::assertEquals($count, count($subscribers));
  }

  /**
   * Returns the Joinup subscription service.
   *
   * @return \Drupal\joinup_subscription\JoinupSubscriptionInterface
   *   The subscription service.
   */
  protected function getSubscriptionService() : JoinupSubscriptionInterface {
    return \Drupal::service('joinup_subscription');
  }

}
