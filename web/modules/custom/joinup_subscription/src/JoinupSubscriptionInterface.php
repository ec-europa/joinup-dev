<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for Joinup subscription service.
 *
 * A subscription is defined as the flagging of certain content entity with a
 * specific flag. The user that performs this process is called subscriber.
 */
interface JoinupSubscriptionInterface {

  /**
   * Gets all the subscribers for a given content entity and a given flag.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity where users have subscribed.
   * @param string $flag_id
   *   The ID of the subscription flag entity.
   *
   * @return \Drupal\user\UserInterface[]
   *   A list of subscriber user accounts.
   */
  public function getSubscribers(ContentEntityInterface $entity, string $flag_id) : array;

}
