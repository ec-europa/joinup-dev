<?php

namespace Drupal\joinup_core\Guard;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup_core\ELibraryCreationOptions;
use Drupal\joinup_core\JoinupRelationManager;
use Drupal\og\MembershipManagerInterface;
use Drupal\state_machine\Guard\GuardInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;
use Drupal\user\RoleInterface;

/**
 * Guard class for the transitions of nodes.
 */
abstract class NodeGuard implements GuardInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current logged in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The OG membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $ogMembershipManager;

  /**
   * The relation manager service.
   *
   * @var \Drupal\joinup_core\JoinupRelationManager
   */
  protected $relationManager;

  /**
   * The allowed transitions array.
   *
   * @var array
   */
  protected $transitions;

  /**
   * Instantiates the NodeGuard service.
   *
   * The classes inheriting this class, should also ensure that they set the
   * protected variable $transitions to be used by the ::allowed() method.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\joinup_core\JoinupRelationManager $relationManager
   *   The relation manager service.
   * @param \Drupal\og\MembershipManagerInterface $ogMembershipManager
   *   The OG membership manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current logged in user.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, JoinupRelationManager $relationManager, MembershipManagerInterface $ogMembershipManager, ConfigFactoryInterface $configFactory, AccountInterface $currentUser) {
    $this->entityTypeManager = $entityTypeManager;
    $this->relationManager = $relationManager;
    $this->ogMembershipManager = $ogMembershipManager;
    $this->configFactory = $configFactory;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public function allowed(WorkflowTransition $transition, WorkflowInterface $workflow, EntityInterface $entity) {
    if (empty($this->transitions)) {
      return FALSE;
    }

    if ($this->currentUser->hasPermission($entity->getEntityType()->getAdminPermission())) {
      return TRUE;
    }

    $allowed_conditions = $this->transitions[$workflow->getId()];

    // Check if the user has one of the allowed system roles.
    $from_state = $this->getState($entity);
    $transition_id = $transition->getId();
    $authorized_roles = isset($allowed_conditions[$transition_id][$from_state]) ? $allowed_conditions[$transition_id][$from_state] : [];

    // If the entity is new, check the eLibrary roles.
    if ($entity->isNew()) {
      // Get the roles according to the eLibrary creation.
      $elibrary_authorized_roles = $this->getElibraryAllowedRoles($entity);
      $authorized_roles = array_intersect($authorized_roles, $elibrary_authorized_roles);
    }

    // If the owner is still allowed, check for ownership.
    if (in_array('owner', $authorized_roles)) {
      if ($entity->getOwnerId() === $this->currentUser->id()) {
        return TRUE;
      }
    }
    $authorized_roles = array_diff($authorized_roles, ['owner']);

    if (array_intersect($authorized_roles, $this->currentUser->getRoles())) {
      return TRUE;
    }

    $parent = $this->relationManager->getParent($entity);
    $membership = $this->ogMembershipManager->getMembership($parent, $this->currentUser);
    return $membership && array_intersect($authorized_roles, $membership->getRolesIds());
  }

  /**
   * Retrieve the initial state value of the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The discussion entity.
   *
   * @return string
   *   The machine name value of the state.
   *
   * @see https://www.drupal.org/node/2745673
   */
  protected function getState(EntityInterface $entity) {
    return $entity->get('field_state')->first()->value;
  }

  /**
   * Returns allowed roles according to the eLibrary creation field.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group content entity.
   *
   * @return array
   *   An array of roles that are allowed.
   */
  protected function getElibraryAllowedRoles(EntityInterface $entity) {
    $roles_array = [
      ELibraryCreationOptions::FACILITATORS => [
        'rdf_entity-collection-facilitator',
        'rdf_entity-solution-facilitator',
        'moderator',
      ],
      ELibraryCreationOptions::MEMBERS => [
        'rdf_entity-collection-facilitator',
        'rdf_entity-solution-facilitator',
        'rdf_entity-collection-member',
        'moderator',
      ],
      ELibraryCreationOptions::REGISTERED_USERS => [
        'rdf_entity-collection-facilitator',
        'rdf_entity-solution-facilitator',
        'rdf_entity-collection-member',
        RoleInterface::AUTHENTICATED_ID,
        'moderator',
      ],
    ];

    $parent = $this->relationManager->getParent($entity);
    if (empty($parent)) {
      // For security reasons, if no parent is returned, return the strictest
      // option.
      return $roles_array[ELibraryCreationOptions::FACILITATORS];
    }

    $e_library_name = $this->getParentElibraryName($parent);
    $e_library_creation = $parent->{$e_library_name}->value;
    return $roles_array[$e_library_creation];
  }

  /**
   * Returns the eLibrary creation machine name.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The parent entity.
   *
   * @return string
   *   The machine name of the eLibrary creation field.
   */
  protected function getParentElibraryName(EntityInterface $entity) {
    $field_array = [
      'collection' => 'field_ar_elibrary_creation',
      'solution' => 'field_is_elibrary_creation',
    ];

    return $field_array[$entity->bundle()];
  }

}
