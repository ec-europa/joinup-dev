<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Interface for Joinup subscription service.
 *
 * A subscription is defined as the flagging of certain content entity with a
 * specific flag. The user that performs this process is called subscriber.
 */
interface JoinupSubscriptionInterface {

  /**
   * Defines a subscription type where a user receives all updates.
   */
  const SUBSCRIBE_ALL = 'all';

  /**
   * Defines a subscription type where a user receives updates on new content.
   *
   * @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4980
   */
  const SUBSCRIBE_NEW = 'new';

  /**
   * Defines a subscription type where a user does not receive any updates.
   */
  const SUBSCRIBE_NONE = 'none';

  /**
   * Gets all the subscribers for a given content entity and a given flag.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity where users have subscribed.
   * @param string $flag_id
   *   The ID of the subscription flag entity.
   *
   * @return \Drupal\Core\Session\AccountInterface[]
   *   An associative array of subscriber user accounts, keyed by user ID.
   */
  public function getSubscribers(ContentEntityInterface $entity, string $flag_id): array;

  /**
   * Subscribes a user to a given content entity through a given flag.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user to subscribe.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to subscribe the user to.
   * @param string $flag_id
   *   The ID of the subscription flag entity that keeps track of the
   *   subscription.
   *
   * @return bool
   *   TRUE if the subscription was successful, FALSE otherwise.
   */
  public function subscribe(AccountInterface $account, ContentEntityInterface $entity, string $flag_id): bool;

  /**
   * Unsubscribes a user from a given content entity through a given flag.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user to unsubscribe.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to unsubscribe the user from.
   * @param string $flag_id
   *   The ID of the subscription flag entity that keeps track of the
   *   subscription.
   */
  public function unsubscribe(AccountInterface $account, ContentEntityInterface $entity, string $flag_id): void;

  /**
   * Checks whether a user is subscribed to a given entity through a given flag.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check if a subscription exists.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity the user is possibly subscribed to.
   * @param string $flag_id
   *   The ID of the subscription flag entity that keeps track of the
   *   subscription.
   *
   * @return bool
   *   TRUE if the user is subscribed, FALSE otherwise.
   */
  public function isSubscribed(AccountInterface $account, ContentEntityInterface $entity, string $flag_id): bool;

}
