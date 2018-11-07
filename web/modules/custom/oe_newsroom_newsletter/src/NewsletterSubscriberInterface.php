<?php

declare(strict_types = 1);

namespace Drupal\oe_newsroom_newsletter;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Interface for classes that allow users to subscribe to newsletters.
 */
interface NewsletterSubscriberInterface extends ContainerInjectionInterface {

  /**
   * Subscribes the given e-mail address to the given newsletter.
   *
   * The newsletter being subscribed to is identified by the given universe and
   * service ID.
   *
   * @param string $email
   *   The e-mail address that is being subscribed.
   * @param string $universe
   *   The Newsroom universe acronym.
   * @param string $service_id
   *   The Newsroom service ID.
   *
   * @throws \Drupal\oe_newsroom_newsletter\Exception\EmailAddressAlreadySubscribedException
   *   Thrown when the passed in e-mail address is already subscribed to the
   *   newsletter identified by the passed in universe acronym and service ID.
   * @throws \Drupal\oe_newsroom_newsletter\Exception\InvalidEmailAddressException
   *   Thrown when the passed in e-mail address is invalid.
   * @throws \Drupal\oe_newsroom_newsletter\Exception\BadResponseException
   *   Thrown when the Newsroom API did not return a valid response. It is not
   *   known whether or not the subscription has been successful.
   */
  public function subscribe(string $email, string $universe, string $service_id): void;

}
