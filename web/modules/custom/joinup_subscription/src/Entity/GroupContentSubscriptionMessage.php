<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription\Entity;

use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\joinup_group\Entity\GroupContentInterface;
use Drupal\joinup_group\Entity\GroupInterface;
use Drupal\joinup_group\Exception\MissingGroupException;
use Drupal\joinup_subscription\Exception\OrphanedGroupContentSubscriptionMessageException;
use Drupal\message\Entity\Message;

/**
 * A group content subscription message.
 */
class GroupContentSubscriptionMessage extends Message implements GroupContentSubscriptionMessageInterface {

  use JoinupBundleClassFieldAccessTrait;

  /**
   * The name of the field that references the group content for this message.
   */
  const GROUP_CONTENT_REFERENCE_FIELD = 'field_group_content';

  /**
   * The message template for this message.
   */
  const TEMPLATE = 'group_content_subscription';

  /**
   * {@inheritdoc}
   *
   * phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found
   */
  public static function create(array $values = []): GroupContentSubscriptionMessageInterface {
    // Delegate to the parent method. This is only overridden to provide the
    // correct return type.
    return parent::create($values);
  }

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

  /**
   * {@inheritdoc}
   */
  public function isOrphanedGroupContentSubscriptionMessage(): bool {
    try {
      $this->getSubscribedGroup();
      return FALSE;
    }
    catch (OrphanedGroupContentSubscriptionMessageException $e) {
      return TRUE;
    }
  }

}
