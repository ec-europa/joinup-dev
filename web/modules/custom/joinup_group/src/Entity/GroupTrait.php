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
   * {@inheritdoc}
   */
  public function getMembership(int $uid, array $states = [OgMembershipInterface::STATE_ACTIVE]): ?OgMembershipInterface {
    assert(is_subclass_of($this, ContentEntityBase::class), __TRAIT__ . ' is intended to be used in bundle classes for content entities.');

    /** @var \Drupal\og\MembershipManagerInterface $membership_manager */
    $membership_manager = \Drupal::service('og.membership_manager');
    return $membership_manager->getMembership($this, $uid, $states);
  }

}
