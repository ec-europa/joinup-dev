<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Entity;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\user\UserInterface;

/**
 * Reusable methods for group bundles.
 */
trait GroupTrait {

  /**
   * {@inheritdoc}
   */
  public function getMembership(?int $uid = NULL, array $states = [OgMembershipInterface::STATE_ACTIVE]): ?OgMembershipInterface {
    assert(is_subclass_of($this, ContentEntityBase::class), __TRAIT__ . ' is intended to be used in bundle classes for content entities.');

    // Default to the current user.
    $uid = $uid ?? \Drupal::currentUser()->id();

    return $this->getMembershipManager()->getMembership($this, $uid, $states);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupAccess(string $permission, ?AccountInterface $user = NULL): AccessResultInterface {
    assert(is_subclass_of($this, ContentEntityBase::class), __TRAIT__ . ' is intended to be used in bundle classes for content entities.');

    /** @var \Drupal\og\OgAccessInterface $og_access */
    $og_access = \Drupal::service('og.access');

    return $og_access->userAccess($this, $permission, $user);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOwners(): array {
    return array_map(function (OgMembershipInterface $membership): UserInterface {
      return $membership->getOwner();
    }, $this->getMembershipManager()->getGroupMembershipsByRoleNames($this, ['administrator']));
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOwnerIds(): array {
    return array_map(function (OgMembershipInterface $membership): int {
      return (int) $membership->getOwnerId();
    }, $this->getMembershipManager()->getGroupMembershipsByRoleNames($this, ['administrator']));
  }

  /**
   * {@inheritdoc}
   */
  public function isGroupOwner(int $uid): bool {
    return in_array($uid, $this->getGroupOwnerIds());
  }

  /**
   * {@inheritdoc}
   */
  public function isSoleGroupOwner(int $uid): bool {
    $owner_ids = $this->getGroupOwnerIds();
    return count($owner_ids) == 1 && in_array($uid, $owner_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function hasGroupPermission(int $uid, string $permission): bool {
    $membership_manager = $this->getMembershipManager();
    if ($membership = $membership_manager->getMembership($this, $uid)) {
      return $membership->hasPermission($permission);
    }
    return FALSE;
  }

  /**
   * Returns the membership manager.
   *
   * @return \Drupal\og\MembershipManagerInterface
   *   The membership manager.
   */
  protected function getMembershipManager(): MembershipManagerInterface {
    return \Drupal::service('og.membership_manager');
  }

}
