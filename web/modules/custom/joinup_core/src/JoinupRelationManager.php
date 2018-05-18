<?php

namespace Drupal\joinup_core;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgMembershipInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service to manage relations for the group content entities.
 */
class JoinupRelationManager implements JoinupRelationManagerInterface, ContainerInjectionInterface {

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
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('og.membership_manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getParent(EntityInterface $entity) {
    $groups = $this->membershipManager->getGroups($entity);
    if (empty($groups['rdf_entity'])) {
      return NULL;
    }

    return reset($groups['rdf_entity']);
  }

  /**
   * {@inheritdoc}
   */
  public function getParentModeration(EntityInterface $entity) {
    $parent = $this->getParent($entity);
    if (!$parent) {
      return NULL;
    }
    $field_array = [
      'collection' => 'field_ar_moderation',
      'solution' => 'field_is_moderation',
    ];

    $moderation = $parent->{$field_array[$parent->bundle()]}->value;
    return $moderation;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentState(EntityInterface $entity) {
    $parent = $this->getParent($entity);
    $field_array = [
      'collection' => 'field_ar_state',
      'solution' => 'field_is_state',
    ];

    $state = $parent->{$field_array[$parent->bundle()]}->first()->value;
    return $state;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentElibrary(EntityInterface $entity) {
    $parent = $this->getParent($entity);
    $field_array = [
      'collection' => 'field_ar_elibrary_creation',
      'solution' => 'field_is_elibrary_creation',
    ];

    $e_library = $parent->{$field_array[$parent->bundle()]}->first()->value;
    return $e_library;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOwners(EntityInterface $entity, array $state = [OgMembershipInterface::STATE_ACTIVE]) {
    $role_id = $entity->getEntityTypeId() . '-' . $entity->bundle() . '-administrator';

    $users = [];
    foreach ($this->getGroupMemberships($entity, $state) as $membership) {
      $user = $membership->getOwner();
      if (!empty($user) && $membership->hasRole($role_id)) {
        $users[$user->id()] = $user;
      }
    }

    return $users;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupUsers(EntityInterface $entity, array $state = [OgMembershipInterface::STATE_ACTIVE]) {
    return array_reduce($this->getGroupMemberships($entity, $state), function ($users, OgMembershipInterface $membership) {
      $user = $membership->getOwner();
      if (!empty($user)) {
        $users[] = $user;
      }
      return $users;
    }, []);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupMemberships(EntityInterface $entity, array $state = [OgMembershipInterface::STATE_ACTIVE]) {
    /** @var \Drupal\og\OgMembershipInterface[] $memberships */
    $memberships = $this->entityTypeManager->getStorage('og_membership')->loadByProperties([
      'state' => $state,
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
    ]);

    return $memberships;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserMembershipsByRole(AccountInterface $user, $role, array $state = [OgMembershipInterface::STATE_ACTIVE]) {
    $storage = $this->entityTypeManager->getStorage('og_membership');

    // Fetch all the memberships of the user, filtered by role and state.
    $query = $storage->getQuery();
    $query->condition('uid', $user->id());
    $query->condition('roles', $role);
    $query->condition('state', $state, 'IN');
    $result = $query->execute();

    return $storage->loadMultiple($result);
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionsWhereSoleOwner(AccountInterface $user) {
    $memberships = $this->getUserMembershipsByRole($user, 'rdf_entity-collection-administrator');

    // Prepare a list of collections where the user is the sole owner.
    $collections = [];
    foreach ($memberships as $membership) {
      $group = $membership->getGroup();
      $owners = $this->getGroupOwners($group);
      if (count($owners) === 1 && array_key_exists($user->id(), $owners)) {
        $collections[$group->id()] = $group;
      }
    }

    return $collections;
  }

}
