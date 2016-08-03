<?php

namespace Drupal\joinup\Traits;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;
use Drupal\og\Og;

/**
 * Contains helper methods regarding the organic groups.
 *
 * @package src\Traits
 */
trait OgTrait {

  /**
   * Creates an Og membership to a group optionally assigning roles as well.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *    The user to be assigned as a group member.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *    The organic group entity.
   * @param \Drupal\og\Entity\OgRole[] $roles
   *    An array of OgRoles to be passed to the membership.
   *
   * @throws \Exception
   *    Throws an exception when the user is anonymous or the entity is not a
   *    group.
   */
  protected function subscribeUserToGroup(AccountInterface $user, EntityInterface $entity, array $roles = []) {
    if (!Og::isGroup($entity->getEntityTypeId(), $entity->bundle())) {
      throw new \Exception("The {$entity->label()} is not a group.");
    }

    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership = OgMembership::create();
    $membership
      ->setUser($user)
      ->setGroup($entity);
    foreach ($roles as $role) {
      $membership->addRole($role);
    }
    $membership->save();
  }

  /**
   * Converts role names into og roles by adding the appropriate prefix.
   *
   * This function does not test if the entity is a group. It merely serves as
   * a name conversion method.
   *
   * @param array $roles
   *    An array of roles to convert names.
   * @param \Drupal\Core\Entity\EntityInterface $group
   *    The group entity.
   *
   * @return array
   *    An array with the converted names.
   */
  protected function convertOgRoleNamesToIds(array $roles, EntityInterface $group) {
    $role_prefix = $group->getEntityTypeId() . '-' . $group->bundle() . '-';
    foreach ($roles as $key => $role) {
      $roles[$key] = $role_prefix . $role;
    }

    return $roles;
  }

  /**
   * Returns the OgRole objects identified by the given role names.
   *
   * @param array $roles
   *    An array of role names for which to return the roles.
   * @param \Drupal\Core\Entity\EntityInterface $group
   *    The group entity to which the roles belong.
   *
   * @return \Drupal\og\Entity\OgRole[]
   *    The OgRole objects.
   */
  protected function getOgRoles(array $roles, EntityInterface $group) {
    $ids = $this->convertOgRoleNamesToIds($roles, $group);
    return array_values(OgRole::loadMultiple($ids));
  }

}
