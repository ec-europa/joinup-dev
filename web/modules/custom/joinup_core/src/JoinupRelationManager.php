<?php

declare(strict_types = 1);

namespace Drupal\joinup_core;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\og\OgRoleInterface;
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
      'collection' => 'field_ar_content_moderation',
      'solution' => 'field_is_content_moderation',
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
  public function getParentContentCreationOption(EntityInterface $entity): string {
    $parent = $this->getParent($entity);
    $field_array = [
      'collection' => 'field_ar_content_creation',
      'solution' => 'field_is_content_creation',
    ];

    return $parent->{$field_array[$parent->bundle()]}->first()->value;
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
    $storage = $this->entityTypeManager->getStorage('rdf_entity');
    $definition = $this->entityTypeManager->getDefinition('rdf_entity');
    $bundle_key = $definition->getKey('bundle');

    $query = $storage->getQuery();
    $query->condition($bundle_key, $bundle);
    return $query->execute();
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

  /**
   * {@inheritdoc}
   */
  public function getContactInformationRelatedGroups(RdfInterface $entity): array {
    // When the user creates a group, they do not have any roles in the group
    // yet. There is no need to have a check for groups when the entity is new.
    if ($entity->isNew()) {
      return [];
    }

    $query = $this->entityTypeManager->getStorage('rdf_entity')->getQuery();
    $condition_or = $query->orConditionGroup();
    // Contact entities are also referenced by releases but this value is
    // inherited by the solution directly so there is no need to check them.
    $condition_or->condition('field_ar_contact_information', $entity->id());
    $condition_or->condition('field_is_contact_information', $entity->id());
    $query->condition($condition_or);
    $ids = $query->execute();

    return empty($ids) ? [] : $this->entityTypeManager->getStorage('rdf_entity')->loadMultiple($ids);
  }

}
