<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\og\OgMembershipInterface;

/**
 * Reusable methods for group bundles.
 */
trait GroupTrait {

  /**
   * Static cache of memberships for this group, keyed by user ID.
   *
   * @var \Drupal\og\OgMembershipInterface[]|NULL[]
   */
  protected $memberships;

  /**
   * {@inheritdoc}
   */
  public function getMembership(int $uid): ?OgMembershipInterface {
    assert(is_subclass_of($this, ContentEntityBase::class), __TRAIT__ . ' is intended to be used in bundle classes for content entities.');
    if (!isset($this->memberships[$uid])) {
      $og_membership_storage = $this->entityTypeManager()->getStorage('og_membership');

      $query = $og_membership_storage
        ->getQuery()
        ->condition('uid', $uid)
        ->condition('entity_type', $this->getEntityTypeId())
        ->condition('entity_id', $this->id());

      $membership_ids = $query->execute();
      if (empty($membership_ids)) {
        return NULL;
      }
      $membership_id = reset($membership_ids);

      /** @var \Drupal\og\OgMembershipInterface|NULL $membership */
      $membership = $og_membership_storage->load($membership_id);

      $this->memberships[$uid] = $membership;
    }

    return $this->memberships[$uid];
  }

}
