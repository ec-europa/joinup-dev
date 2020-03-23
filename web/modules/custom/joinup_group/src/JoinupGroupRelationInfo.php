<?php

declare(strict_types = 1);

namespace Drupal\joinup_group;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service to manage relations for the group content entities.
 */
class JoinupGroupRelationInfo implements JoinupGroupRelationInfoInterface, ContainerInjectionInterface {

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
