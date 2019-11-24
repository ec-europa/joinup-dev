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
use Drupal\og\OgRoleInterface;
use Drupal\rdf_entity\RdfInterface;
use Drupal\sparql_entity_storage\SparqlEntityStorageInterface;
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
   * The sparql entity storage.
   *
   * @var \Drupal\sparql_entity_storage\SparqlEntityStorageInterface
   */
  protected $sparqlStorage;

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
    $memberships = $this->membershipManager->getGroupMembershipsByRoleNames($entity, ['administrator'], $states);

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
    return $this->membershipManager->getGroupMembershipsByRoleNames($entity, [OgRoleInterface::AUTHENTICATED], $states);
  }

  /**
   * {@inheritdoc}
   */
  public function getUserMembershipsByRole(AccountInterface $user, string $role, array $states = [OgMembershipInterface::STATE_ACTIVE]): array {
    $storage = $this->getRdfStorage();

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
    $storage = $this->getRdfStorage();
    $definition = $this->entityTypeManager->getDefinition('rdf_entity');
    $bundle_key = $definition->getKey('bundle');

    $query = $storage->getQuery();
    $query->condition($bundle_key, $bundle);
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getContactInformationRelatedGroups(RdfInterface $entity): array {
    $query = $this->getRdfStorage()->getQuery();
    $condition_or = $query->orConditionGroup();
    // Contact entities are also referenced by releases but this value is
    // inherited by the solution directly so there is no need to check them.
    $condition_or->condition('field_ar_contact_information', $entity->id());
    $condition_or->condition('field_is_contact_information', $entity->id());
    $query->condition($condition_or);
    $ids = $query->execute();

    return empty($ids) ? [] : $this->getRdfStorage()->loadMultiple($ids);
  }

  /**
   * Returns the rdf storage class.
   *
   * @return \Drupal\sparql_entity_storage\SparqlEntityStorageInterface
   *   The rdf storage class.
   */
  protected function getRdfStorage(): SparqlEntityStorageInterface {
    if (empty($this->sparqlStorage)) {
      try {
        // Since the Joinup Core module depends on the RDF Entity module we can
        // reasonably assume that the entity storage is defined and is valid. If
        // it is not this is due to exceptional circumstances occuring at runtime.
        $this->sparqlStorage = $this->entityTypeManager->getStorage('rdf_entity');
      }
      catch (InvalidPluginDefinitionException $e) {
        throw new \RuntimeException('The RDF entity storage is not valid.');
      }
      catch (PluginNotFoundException $e) {
        throw new \RuntimeException('The RDF entity storage is not defined.');
      }
    }
    return $this->sparqlStorage;
  }

}
