<?php

namespace Drupal\joinup_document\Guard;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup_document\JoinupDocumentRelationManager;
use Drupal\joinup_user\WorkflowUserProvider;
use Drupal\og\MembershipManagerInterface;
use Drupal\state_machine\Guard\GuardInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;
use Drupal\user\RoleInterface;

/**
 * Class JoinupDocumentFulfillmentGuard.
 */
class JoinupDocumentFulfillmentGuard implements GuardInterface {

  /**
   * Elibrary option defining that only facilitators can create content.
   */
  const ELIBRARY_ONLY_FACILITATORS = 0;

  /**
   * Elibrary option defining that members and facilitators can create content.
   */
  const ELIBRARY_MEMBERS_FACILITATORS = 1;

  /**
   * Elibrary option defining that any registered user can create content.
   */
  const ELIBRARY_REGISTERED_USERS = 2;

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
   * The documents relation manager.
   *
   * @var \Drupal\joinup_document\JoinupDocumentRelationManager
   */
  protected $relationManager;

  /**
   * The workflow user provider service.
   *
   * @var \Drupal\joinup_user\WorkflowUserProvider
   */
  protected $workflowUserProvider;

  /**
   * Instantiates the JoinupDocumentFulfillmentGuard service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\joinup_user\WorkflowUserProvider $workflowUserProvider
   *   The workflow user provider service.
   * @param \Drupal\joinup_document\JoinupDocumentRelationManager $relationManager
   *   The documents relation service.
   * @param \Drupal\og\MembershipManagerInterface $ogMembershipManager
   *   The OG membership manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current logged in user.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, WorkflowUserProvider $workflowUserProvider, JoinupDocumentRelationManager $relationManager, MembershipManagerInterface $ogMembershipManager, ConfigFactoryInterface $configFactory, AccountInterface $currentUser) {
    $this->configFactory = $configFactory;
    $this->currentUser = $currentUser;
    $this->entityTypeManager = $entityTypeManager;
    $this->ogMembershipManager = $ogMembershipManager;
    $this->relationManager = $relationManager;
    $this->workflowUserProvider = $workflowUserProvider;
  }

  /**
   * {@inheritdoc}
   */
  public function allowed(WorkflowTransition $transition, WorkflowInterface $workflow, EntityInterface $entity) {
    if ($this->workflowUserProvider->getUser()->hasPermission('bypass node access')) {
      return TRUE;
    }

    $allowed_conditions = $this->configFactory->get('joinup_document.settings')->get('transitions')[$workflow->getId()];

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
      if ($entity->getOwnerId() === $this->workflowUserProvider->getUser()->id()) {
        return TRUE;
      }
    }
    $authorized_roles = array_diff($authorized_roles, ['owner']);

    $user = $this->workflowUserProvider->getUser();
    if (array_intersect($authorized_roles, $user->getRoles())) {
      return TRUE;
    }

    $parent = $this->relationManager->getDocumentParent($entity);
    $membership = $this->ogMembershipManager->getMembership($parent, $user);
    return $membership && array_intersect($authorized_roles, $membership->getRolesIds());
  }

  /**
   * Retrieve the initial state value of the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *    The document entity.
   *
   * @return string
   *    The machine name value of the state.
   *
   * @see https://www.drupal.org/node/2745673
   */
  protected function getState(EntityInterface $entity) {
    return $entity->get('field_state')->first()->value;
  }

  /**
   * Returns allowed roles according to the eLibrary creation field.
   *
   * @param \Drupal\Core\Entity\EntityInterface $document
   *    The document entity.
   *
   * @return array
   *    An array of roles that are allowed.
   */
  protected function getElibraryAllowedRoles(EntityInterface $document) {
    $roles_array = [
      self::ELIBRARY_ONLY_FACILITATORS => [
        'rdf_entity-collection-facilitator',
        'rdf_entity-solution-facilitator',
        'moderator',
      ],
      self::ELIBRARY_MEMBERS_FACILITATORS => [
        'rdf_entity-collection-facilitator',
        'rdf_entity-solution-facilitator',
        'rdf_entity-collection-member',
        'moderator',
      ],
      self::ELIBRARY_REGISTERED_USERS => [
        'rdf_entity-collection-facilitator',
        'rdf_entity-solution-facilitator',
        'rdf_entity-collection-member',
        RoleInterface::AUTHENTICATED_ID,
        'moderator',
      ],
    ];

    $parent = $this->relationManager->getDocumentParent($document);
    if (empty($parent)) {
      // For security reasons, if no parent is returned, return the strictest
      // option.
      return $roles_array[self::ELIBRARY_ONLY_FACILITATORS];
    }

    $elibrary_name = $this->getParentElibraryName($parent);
    $elibrary_creation = $parent->{$elibrary_name}->value;
    return $roles_array[$elibrary_creation];
  }

  /**
   * Returns the eLibrary creation machine name.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *    The parent entity.
   *
   * @return string
   *    The machine name of the eLibrary creation field.
   */
  protected function getParentElibraryName(EntityInterface $entity) {
    $field_array = [
      'collection' => 'field_ar_elibrary_creation',
      'solution' => 'field_is_elibrary_creation',
    ];

    return $field_array[$entity->bundle()];
  }

}
