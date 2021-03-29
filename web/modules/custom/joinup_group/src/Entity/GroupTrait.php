<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Entity;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup_core\Entity\OutdatedContentTrait;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\user\UserInterface;

/**
 * Reusable methods for group bundles.
 */
trait GroupTrait {

  use OutdatedContentTrait;

  /**
   * {@inheritdoc}
   */
  public function getMembership(?int $uid = NULL, array $states = [OgMembershipInterface::STATE_ACTIVE]): ?OgMembershipInterface {
    assert(is_subclass_of($this, GroupInterface::class), __TRAIT__ . ' is intended to be used in bundle classes for group entities.');

    // Default to the current user.
    $uid = $uid ?? \Drupal::currentUser()->id();

    return $this->getMembershipManager()->getMembership($this, $uid, $states);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupAccess(string $permission, ?AccountInterface $user = NULL): AccessResultInterface {
    assert(is_subclass_of($this, GroupInterface::class), __TRAIT__ . ' is intended to be used in bundle classes for group entities.');

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
   * {@inheritdoc}
   */
  public function isModerated(): bool {
    return (int) $this->getMainPropertyValue($this->getGroupModerationFieldName()) === GroupInterface::PRE_MODERATION;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentCreators(): string {
    return $this->getMainPropertyValue($this->getContentCreationFieldName());
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupContentIds(): array {
    $group_content = $this->doGetGroupContentIds();
    // Ensure that the results are sorted.
    ksort($group_content);
    array_walk($group_content, function (array &$ids_by_bundle): void {
      ksort($ids_by_bundle);
      array_walk($ids_by_bundle, function (array &$ids): void {
        // Sorting using array_walk($ids_by_bundle, 'sort') short form, just
        // doesn't work because array_walk() passes the array key as a second
        // callback parameter. So sort() will receive the array key as second
        // parameter, but the function expects a total different value there.
        sort($ids);
      });
    });
    return $group_content;
  }

  /**
   * Processes and returns a list of group content entities.
   *
   * @return array
   *   An associative array keyed by the group content entity type ID. Each
   *   value is an associative array keyed by entity bundle and having the node
   *   IDs as values.
   */
  abstract protected function doGetGroupContentIds(): array;

  /**
   * Returns a list of group content node IDs, grouped by node type.
   *
   * @return int[][]
   *   An associative array keyed by node type and having node IDs as values.
   */
  protected function getNodeGroupContent(): array {
    $storage = $this->entityTypeManager()->getStorage('node');

    $nids = $storage->getQuery()
      ->condition(OgGroupAudienceHelperInterface::DEFAULT_FIELD, $this->id())
      ->execute();

    if (!$nids) {
      return [];
    }

    $nids_per_bundle = [];
    foreach ($storage->loadMultiple($nids) as $nid => $node) {
      $nids_per_bundle[$node->bundle()][] = $nid;
    }

    return $nids_per_bundle;
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
