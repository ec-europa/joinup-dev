<?php

namespace Drupal\joinup\Traits;

use Drupal\Core\Entity\EntityInterface;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * Contains helper methods regarding the organic groups.
 *
 * @package src\Traits
 */
trait OgTrait {

  /**
   * Creates an Og membership to a group optionally assigning roles as well.
   *
   * @param int $user_id
   *    The ID of the user to be assigned as an Og member.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *    The organic group entity.
   * @param array $roles
   *    An array of roles to be passed to the membership. The full ID should be
   *    passed.
   *
   * @throws \Exception
   *    Throws an exception when the user is anonymous or the entity is not a
   *    group.
   */
  protected function subscribeUserToGroup($user_id, EntityInterface $entity, array $roles = []) {
    if (!Og::isGroup($entity->getEntityTypeId(), $entity->bundle())) {
      throw new \Exception("The {$entity->label()} is not a group.");
    }

    /** @var \Drupal\og\OgMembershipInterface $membership */
    $membership = OgMembership::create(['type' => OgMembershipInterface::TYPE_DEFAULT]);
    $membership
      ->setUser($user_id)
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
      $roles[$key] = $role_prefix . $role;
    }

    return $roles;
  }

  /**
   * Creates a membership for a user to an rdf entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $group
   *    The rdf entity group.
   * @param string $user_name
   *    The user's username.
   * @param string $roles
   *    The roles that the user will be assigned. multiple roles should be
   *    passed as a string separating the roles with a comma.
   *
   * @throws \Exception
   *    Throws an exception when the user is not found as memberships cannot be
   *    created for anonymous users.
   */
  protected function createOgMembership(RdfInterface $group, $user_name, $roles) {
    $member = user_load_by_name($user_name);
    if (empty($member)) {
      throw new \Exception('User ' . $user_name . ' not found.');
    }

    if (!empty($roles)) {
      $roles = $this->convertOgRoleNamesToIds(explode(', ', $roles), $group);
    }

    $this->subscribeUserToGroup($member->id(), $group, $roles);
  }

}
