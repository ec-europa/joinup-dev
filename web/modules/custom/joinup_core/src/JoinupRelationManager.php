<?php

declare(strict_types = 1);

namespace Drupal\joinup_core;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service to manage relations for the group content entities.
 *
 * @todo This module depends on functionality provided by a number of modules
 *   such as Collection and Solution that depend on joinup_core themselves. This
 *   causes a circular dependency. It should be moved to the installation
 *   profile.
 *
 * @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4543
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
  public function getParent(EntityInterface $entity): ?RdfInterface {
    $groups = $this->membershipManager->getGroups($entity);
    if (empty($groups['rdf_entity'])) {
      return NULL;
    }

    return reset($groups['rdf_entity']);
  }

  /**
   * {@inheritdoc}
   */
  public function getParentModeration(EntityInterface $entity): ?int {
    $parent = $this->getParent($entity);
    if (!$parent) {
      return NULL;
    }
    $field_array = [
      'collection' => 'field_ar_moderation',
      'solution' => 'field_is_moderation',
    ];

    $moderation = $parent->{$field_array[$parent->bundle()]}->value;
    return (int) $moderation;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentState(EntityInterface $entity): string {
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
  // @codingStandardsIgnoreLine
  public function getParentELibraryCreationOption(EntityInterface $entity): int {
    $parent = $this->getParent($entity);
    $field_array = [
      'collection' => 'field_ar_elibrary_creation',
      'solution' => 'field_is_elibrary_creation',
    ];

    $e_library = (int) $parent->{$field_array[$parent->bundle()]}->first()->value;
    return $e_library;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOwners(EntityInterface $entity, array $states = [OgMembershipInterface::STATE_ACTIVE]): array {
    $memberships = $this->getGroupMembershipsByRoles($entity, ['administrator'], $states);

    $users = [];
    foreach ($memberships as $membership) {
      $user = $membership->getOwner();
      if (!empty($user)) {
        $users[$user->id()] = $user;
      }
    }

    return $users;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupUsers(EntityInterface $entity, array $states = [OgMembershipInterface::STATE_ACTIVE]): array {
    return array_reduce($this->getGroupMemberships($entity, $states), function ($users, OgMembershipInterface $membership) {
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
  public function getGroupMemberships(EntityInterface $entity, array $states = [OgMembershipInterface::STATE_ACTIVE]): array {
    return $this->getGroupMembershipsByRoles($entity, [], $states);
  }

  /**
   * {@inheritdoc}
   */
  public function getUserMembershipsByRole(AccountInterface $user, string $role, array $states = [OgMembershipInterface::STATE_ACTIVE]): array {
    $storage = $this->entityTypeManager->getStorage('og_membership');

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
  public function getCollectionsWhereSoleOwner(AccountInterface $user): array {
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

  /**
   * {@inheritdoc}
   */
  public function getGroupMembershipsByRoles(EntityInterface $entity, array $role_names, array $states = [OgMembershipInterface::STATE_ACTIVE]): array {
    $entity_type_id = $entity->getEntityTypeId();

    $properties = [
      'state' => $states,
      'entity_type' => $entity_type_id,
      'entity_id' => $entity->id(),
    ];

    // Optionally filter by role names.
    if (!empty($role_names)) {
      $bundle_id = $entity->bundle();

      $role_ids = array_map(function (string $role_name) use ($entity_type_id, $bundle_id): string {
        return implode('-', [$entity_type_id, $bundle_id, $role_name]);
      }, $role_names);

      $properties['roles'] = $role_ids;
    }

    return $this->entityTypeManager->getStorage('og_membership')->loadByProperties($properties);
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionIds(): array {
    return $this->getRdfEntityIdsByBundle('collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getSolutionIds(): array {
    return $this->getRdfEntityIdsByBundle('solution');
  }

  /**
   * Returns the entity IDs of the RDF entities with the given bundle ID.
   *
   * @param string $bundle
   *   The bundle ID.
   *
   * @return string[]
   *   An array of entity IDs.
   */
  protected function getRdfEntityIdsByBundle(string $bundle): array {
    try {
      // Since the Joinup Core module depends on the RDF Entity module we can
      // reasonably assume that the entity storage is defined and is valid. If
      // it is not this is due to exceptional circumstances occuring at runtime.
      $storage = $this->entityTypeManager->getStorage('rdf_entity');
      $definition = $this->entityTypeManager->getDefinition('rdf_entity');
    }
    catch (InvalidPluginDefinitionException $e) {
      throw new \RuntimeException('The RDF entity storage is not valid.');
    }
    catch (PluginNotFoundException $e) {
      throw new \RuntimeException('The RDF entity storage is not defined.');
    }

    $bundle_key = $definition->getKey('bundle');

    $query = $storage->getQuery();
    $query->condition($bundle_key, $bundle);
    return $query->execute();
  }

}
