<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription\Entity;

use Drupal\message\Entity\Message;

/**
 * A collection content subscription message.
 */
class CollectionContentSubscriptionMessage extends Message implements CollectionContentSubscriptionMessageInterface {

  /**
   * The name of the field that references the group content for this message.
   */
  const GROUP_CONTENT_REFERENCE_FIELD = 'field_collection_content';

  use GroupContentSubscriptionMessageTrait;

}
