<?php

declare(strict_types = 1);

namespace Drupal\oe_newsroom_newsletter;

use Drupal\oe_newsroom_newsletter\NewsletterSubscriber\NewsletterSubscriberInterface;

/**
 * Interface for services that instantiate newsletter subscribers.
 */
interface SubscriberFactoryInterface {

  /**
   * Returns the newsletter subscriber.
   *
   * @return \Drupal\oe_newsroom_newsletter\NewsletterSubscriber\NewsletterSubscriberInterface
   *   The newsletter subscriber.
   */
  public function get(): NewsletterSubscriberInterface;

}
