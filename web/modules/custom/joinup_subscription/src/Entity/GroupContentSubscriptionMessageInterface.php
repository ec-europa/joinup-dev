<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription\Entity;

use Drupal\joinup_group\Entity\GroupContentInterface;
use Drupal\joinup_group\Entity\GroupInterface;
use Drupal\message\MessageInterface;

/**
 * Interface for group content subscription messages.
 */
interface GroupContentSubscriptionMessageInterface extends MessageInterface {

  /**
   * Returns the group content that is the subject of this subscription message.
   *
   * @return \Drupal\joinup_group\Entity\GroupContentInterface
   *   The group content.
   *
   * @throws \Drupal\joinup_subscription\Exception\OrphanedGroupContentSubscriptionMessageException
   *   Thrown when the group content entity no longer exists. This can happen if
   *   the content has been removed after the message has been created.
   */
  public function getSubscribedGroupContent(): GroupContentInterface;

  /**
   * Returns the group that is referenced by this message's group content.
   *
   * @return \Drupal\joinup_group\Entity\GroupInterface
   *   The group.
   *
   * @throws \Drupal\joinup_subscription\Exception\OrphanedGroupContentSubscriptionMessageException
   *   Thrown when the group content entity or the group entity no longer
   *   exists. This can happen if either of them has been removed after the
   *   message has been created.
   */
  public function getSubscribedGroup(): GroupInterface;

}
