<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription\Entity;

use Drupal\joinup_group\Entity\GroupContentInterface;
use Drupal\message\MessageInterface;

/**
 * Interface for group content subscription messages.
 */
interface GroupContentSubscriptionMessageInterface extends MessageInterface {

  /**
   * Returns the group content that is the subject of this subscription message.
   *
   * @return \Drupal\joinup_group\Entity\GroupContentInterface|null
   *   The group content, or NULL if the content the message is referring to has
   *   been deleted.
   */
  public function getSubscribedGroupContent(): ?GroupContentInterface;

}
