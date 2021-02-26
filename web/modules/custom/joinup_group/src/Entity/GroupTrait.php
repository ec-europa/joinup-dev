<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\user\UserInterface;

/**
 * Reusable methods for group bundles.
 */
trait GroupTrait {

  /**
   * {@inheritdoc}
   */
  public function getMembership(int $uid, array $states = [OgMembershipInterface::STATE_ACTIVE]): ?OgMembershipInterface {
    assert(is_subclass_of($this, ContentEntityBase::class), __TRAIT__ . ' is intended to be used in bundle classes for content entities.');

    return $this->getMembershipManager()->getMembership($this, $uid, $states);
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
   * {@inheritdoc}
   */
  public function getGroupContentIds(): array {
    $group_content = $this->doGetGroupContentIds();
    // Ensure that the results are sorted.
    ksort($group_content);
    array_walk($group_content, function (array &$ids): void {
      // Sorting using array_walk($group_content, 'sort') short form, just
      // doesn't work because array_walk() passes the array key as a second
      // callback parameter. So sort() will receive the array key as second
      // parameter, but the function expects a total different value there.
      sort($ids);
    });
    return $group_content;
  }

  /**
   * Processes and returns a list of group content entities.
   *
   * @return array
   *   An associative array keyed by the group content entity type ID and having
   *   an indexed array of entity IDs as values.
   */
  abstract protected function doGetGroupContentIds(): array;

  /**
   * Returns a list of group content node IDs.
   *
   * @return int[]
   *   A list of group content node IDs.
   */
  protected function getNodeGroupContent(): array {
    return array_values($this->entityTypeManager()
      ->getStorage('node')
      ->getQuery()
      ->condition(OgGroupAudienceHelperInterface::DEFAULT_FIELD, $this->id())
      ->execute());
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
