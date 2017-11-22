<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for Joinup subscription service.
 *
 * A subscription is defined as flagging a certain content entity with a
 * specific flag. The user that performs this process is called subscriber.
 */
interface JoinupSubscriptionInterface {

  /**
   * Gets all the subscribers to a given content entity, for a given flag.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity where users have subscribed.
   * @param string $flag_id
   *   The ID of the subscription flag entity.
   *
   * @return \Drupal\Core\Session\AccountInterface[]
   *   A list of subscribers user accounts.
   */
  public function getSubscribers(ContentEntityInterface $entity, string $flag_id) : array;

  /**
   * Gets the emails of the subscribers to a given content entity, for a flag.
   *
   * This is the same as ::getSubscribers() but returns the emails of the
   * subscribers. Useful to be used when sending notifications as a shorthand.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity where users have subscribed.
   * @param string $flag_id
   *   The ID of the subscription flag entity.
   *
   * @return string[]
   *   A list of subscribers emails.
   */
  public function getSubscribersAsMails(ContentEntityInterface $entity, string $flag_id) : array;

}
