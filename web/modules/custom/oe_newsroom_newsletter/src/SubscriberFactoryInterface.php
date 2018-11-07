<?php

declare(strict_types = 1);

namespace Drupal\oe_newsroom_newsletter;

/**
 * Interface for services that instantiate newsletter subscribers.
 */
interface SubscriberFactoryInterface {

  /**
   * Returns the newsletter subscriber.
   *
   * @return \Drupal\oe_newsroom_newsletter\NewsletterSubscriberInterface
   *   The newsletter subscriber.
   */
  public function get(): NewsletterSubscriberInterface;

}
