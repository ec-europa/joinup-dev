<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\joinup\Traits\NodeTrait;
use Drupal\joinup_subscription\JoinupDiscussionSubscriptionInterface;
use PHPUnit\Framework\Assert;

/**
 * Behat step definitions for testing discussions.
 */
class JoinupDiscussionContext extends RawDrupalContext {

  use NodeTrait;

  /**
   * Navigates to the canonical page display of a discussion.
   *
   * @param string $title
   *   The name of the discussion.
   *
   * @When I go to the :title discussion
   * @When I visit the :title discussion
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function visitDiscussion(string $title): void {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->getNodeByTitle($title, 'discussion');
    $this->visitPath($node->toUrl()->toString());
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
  public function assertSubscribers(string $title, int $count): void {
    $discussion = $this->getNodeByTitle($title, 'discussion');
    $subscribers = $this->getDiscussionSubscriptionService()->getSubscribers($discussion, 'subscribe_discussions');
    Assert::assertEquals($count, count($subscribers));
  }

  /**
   * Returns the Joinup subscription service.
   *
   * @return \Drupal\joinup_subscription\JoinupDiscussionSubscriptionInterface
   *   The subscription service.
   */
  protected function getDiscussionSubscriptionService(): JoinupDiscussionSubscriptionInterface {
    return \Drupal::service('joinup_subscription.discussion_subscription');
  }

}
