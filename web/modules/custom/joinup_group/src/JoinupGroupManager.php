<?php

declare(strict_types = 1);

namespace Drupal\joinup_group;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\og\OgRoleInterface;

/**
 * Helper methods related to the Joinup groups.
 */
class JoinupGroupManager implements JoinupGroupManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The OG membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * Constructs a JoinupRelationshipManager object.
   *
   * @param \Drupal\og\MembershipManagerInterface $membershipManager
   *   The OG membership manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(MembershipManagerInterface $membershipManager, EntityTypeManagerInterface $entityTypeManager) {
    $this->membershipManager = $membershipManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupsWhereSoleOwner(AccountInterface $user): array {
    $groups = [];
    foreach (['collection', 'solution'] as $bundle) {
      $memberships = $this->getUserMembershipsByRole($user, "rdf_entity-{$bundle}-administrator");

      // Prepare a list of groups where the user is the sole owner.
      foreach ($memberships as $membership) {
        /** @var \Drupal\joinup_group\Entity\GroupInterface $group */
        $group = $membership->getGroup();
        if ($group->isSoleGroupOwner((int) $user->id())) {
          $groups[$group->id()] = $group;
        }
      }
    }

    return $groups;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserGroupMembershipsByBundle(AccountInterface $user, string $entity_type_id, string $bundle_id, array $states = [OgMembershipInterface::STATE_ACTIVE]): array {
    $storage = $this->getOgMembershipStorage();
    $query = $storage->getQuery()
      ->condition('uid', $user->id())
      ->condition('entity_type', $entity_type_id)
      ->condition('entity_bundle', $bundle_id)
      ->condition('state', $states, 'IN');
    return $storage->loadMultiple($query->execute());
  }

  /**
   * {@inheritdoc}
   */
  public function getUserMembershipsByRole(AccountInterface $user, string $role, array $states = [OgMembershipInterface::STATE_ACTIVE]): array {
    $storage = $this->getOgMembershipStorage();

    // Fetch all the memberships of the user, filtered by role and state.
    $query = $storage->getQuery();
    $query->condition('uid', $user->id());
    $query->condition('roles', $role);
    $query->condition('state', $states, 'IN');
    $result = $query->execute();

    return $storage->loadMultiple($result);
  }

  /**
   * {@inheritdoc}
   */
  public function isGroupOwner(EntityInterface $group, AccountInterface $user): bool {
    $membership = $this->membershipManager->getMembership($group, $user->id());
    if (empty($membership)) {
      return FALSE;
    }

    // OG provides a flag in OG roles called 'is_admin'. We could have used this
    // flag and iterate over the roles to search for admin roles of the user but
    // this flag is apparently meant for create a UID 1 like role which always
    // has access to anything since even the ::hasPermission returns always
    // TRUE for admin roles. That is something we do not want.
    // Thus, admin is considered a user with the administrator role instead.
    $administrator_role = $membership->getGroupEntityType() . '-' . $membership->getGroupBundle() . '-' . OgRoleInterface::ADMINISTRATOR;
    return $membership->hasRole($administrator_role);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOwners(EntityInterface $entity, array $states = [OgMembershipInterface::STATE_ACTIVE]): array {
    $memberships = $this->membershipManager->getGroupMembershipsByRoleNames($entity, ['administrator'], $states);

    $users = [];
    /** @var \Drupal\og\OgMembershipInterface $membership */
    foreach ($memberships as $membership) {
      $user = $membership->getOwner();
      if (!empty($user)) {
        $users[$user->id()] = $user;
      }
    }

    return $users;
  }

  /**
   * Returns the entity storage for OgMembership entities.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The entity storage.
   */
  protected function getOgMembershipStorage(): EntityStorageInterface {
    // Since entities can be dynamically defined in Drupal the generic entity
    // type manager service can throw exceptions in case entities are not
    // available. However these circumstances do not apply to us since we are
    // requesting the OgMembership entities which are defined in code in the OG
    // module on which we correctly depend. Transform these exceptions to
    // unchecked runtime exceptions so we don't need to document these all the
    // way up the call stack.
    try {
      return $this->entityTypeManager->getStorage('og_membership');
    }
    catch (InvalidPluginDefinitionException $e) {
      throw new \RuntimeException('The OgMembership entity has an invalid plugin definition.', NULL, $e);
    }
    catch (PluginNotFoundException $e) {
      throw new \RuntimeException('The OgMembership entity storage does not exist.', NULL, $e);
    }
  }

}
