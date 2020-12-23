<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription\Entity;

use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\joinup_group\Entity\GroupContentInterface;
use Drupal\message\Entity\Message;

/**
 * A collection content subscription message.
 */
class CollectionContentSubscriptionMessage extends Message implements CollectionContentSubscriptionMessageInterface {

  use JoinupBundleClassFieldAccessTrait;

  /**
   * {@inheritdoc}
   */
  public function getSubscribedGroupContent(): ?GroupContentInterface {
    $referenced_entity = $this->getFirstReferencedEntity('field_collection_content');
    if ($referenced_entity instanceof GroupContentInterface) {
      return $referenced_entity;
    }

    return NULL;
  }

}
