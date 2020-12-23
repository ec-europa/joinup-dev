<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription\Entity;

use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\joinup_group\Entity\GroupContentInterface;
use Drupal\joinup_group\Entity\GroupInterface;
use Drupal\joinup_group\Exception\MissingGroupException;
use Drupal\joinup_subscription\Exception\OrphanedGroupContentSubscriptionMessageException;

/**
 * Contains common logic for group content subscription message classes.
 */
trait GroupContentSubscriptionMessageTrait {

  use JoinupBundleClassFieldAccessTrait;

  /**
   * {@inheritdoc}
   */
  public function getSubscribedGroupContent(): GroupContentInterface {
    $referenced_entity = $this->getFirstReferencedEntity(self::GROUP_CONTENT_REFERENCE_FIELD);
    if ($referenced_entity instanceof GroupContentInterface) {
      return $referenced_entity;
    }

    throw new OrphanedGroupContentSubscriptionMessageException();
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscribedGroup(): GroupInterface {
    try {
      return $this->getSubscribedGroupContent()->getGroup();
    }
    catch (MissingGroupException | OrphanedGroupContentSubscriptionMessageException $e) {
      throw new OrphanedGroupContentSubscriptionMessageException('Cannot retrieve group from orphaned group content subscription message.', NULL, $e);
    }
  }

}
