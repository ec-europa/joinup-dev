<?php

namespace Drupal\joinup\Traits;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;

/**
 * Contains helper methods regarding the organic groups.
 *
 * @package src\Traits
 */
trait OgTrait {

  /**
   * User with a given og role is already logged in.
   *
   * If the user has more than the required roles, he might have permissions
   * from the rest of the roles that will lead the test to a false positive.
   * For this reason, we request check for the specific roles passed.
   *
   * @param array $roles
   *   An array of roles to check.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A single role, or multiple comma-separated roles in a single string.
   *
   * @return bool
   *   Returns TRUE if the current logged in user has this role (or roles).
   */
  protected function loggedInWithOgRole($roles, EntityInterface $entity) {
    if (!$this->loggedIn() || !$this->user) {
      return FALSE;
    }

    $membership = Og::getMembership($this->user, $entity);
    if (empty($membership)) {
      return FALSE;
    }
    if ($roles == $membership->getRolesIds()) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Creates an Og membership to a group optionally assigning roles as well.
   *
   * @param AccountInterface $user
   *    The user to be assigned as an Og member.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *    The organic group entity.
   * @param array $roles
   *    An array of roles to be passed to the membership. The full id should be
   *    passed.
   *
   * @throws \Exception
   *    Throws an exception when the user is anonymous or the entity is not a
   *    group.
   */
  protected function subscribeUserToGroup(AccountInterface $user, EntityInterface $entity, $roles = []) {
    if ($user->isAnonymous()) {
      throw new \Exception("Memberships cannot be created for anonymous users.");
    }
    if (!Og::isGroup($entity->getEntityTypeId(), $entity->bundle())) {
      throw new \Exception("The {$entity->label()} is not a group.");
    }

    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership = OgMembership::create(['type' => OgMembershipInterface::TYPE_DEFAULT]);
    $membership
      ->setUser($user->id())
      ->setEntityId($entity->id())
      ->setGroupEntityType($entity->getEntityTypeId())
      ->setFieldName($membership->getFieldName());
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
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *    The group entity.
   *
   * @return array
   *    An array with the converted names.
   */
  protected function convertOgRoleNamesToIds(array $roles, EntityInterface $entity) {
    $role_prefix = $entity->getEntityTypeId() . '-' . $entity->bundle() . '-';
    foreach ($roles as $key => $role) {
      $roles[$key] = $role_prefix . $roles;
    }

    return $roles;
  }

}
