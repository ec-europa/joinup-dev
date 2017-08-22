<?php

namespace Drupal\joinup_core;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;

/**
 * Contains helper methods to retrieve workflow related data from entities.
 */
class WorkflowHelper implements WorkflowHelperInterface {

  /**
   * The account switcher service.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $accountSwitcher;

  /**
   * The current user proxy.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * Constructs a WorkflowHelper.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The service that contains the current user.
   * @param \Drupal\Core\Session\AccountSwitcherInterface $accountSwitcher
   *   The account switcher interface.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\og\MembershipManagerInterface $membershipManager
   *   The membership manager service.
   */
  public function __construct(AccountProxyInterface $currentUser, AccountSwitcherInterface $accountSwitcher, EntityFieldManagerInterface $entityFieldManager, MembershipManagerInterface $membershipManager) {
    $this->accountSwitcher = $accountSwitcher;
    $this->currentUser = $currentUser;
    $this->entityFieldManager = $entityFieldManager;
    $this->membershipManager = $membershipManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableStatesLabels(FieldableEntityInterface $entity, AccountInterface $account = NULL) {
    $allowed_transitions = $this->getAvailableTransitions($entity, $account);

    $allowed_states = array_map(function (WorkflowTransition $transition) {
      return (string) $transition->getToState()->getLabel();
    }, $allowed_transitions);

    return $allowed_states;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableTransitions(FieldableEntityInterface $entity, AccountInterface $account = NULL) {
    // Set the current user so that states available are retrieved for the
    // specific account.
    // The proper solution would be to pass the account to the state_machine
    // field method, to avoid these account switch trickeries.
    // @todo change this once the upstream issue is fixed.
    // @see https://www.drupal.org/node/2776969
    $account_switched = FALSE;
    if ($account !== NULL && $account->id() !== $this->currentUser->id()) {
      $this->accountSwitcher->switchTo($account);
      $account_switched = TRUE;
    }

    $transitions = $this->getEntityStateField($entity)->getTransitions();

    if ($account_switched) {
      $this->accountSwitcher->switchBack();
    }

    return $transitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityStateField(FieldableEntityInterface $entity) {
    $field_definition = $this->getEntityStateFieldDefinition($entity->getEntityTypeId(), $entity->bundle());
    if ($field_definition === NULL) {
      throw new \Exception('No state fields were found in the entity.');
    }
    return $entity->{$field_definition->getName()}->first();
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityStateFieldDefinition($entity_type_id, $bundle_id) {
    if ($field_definitions = $this->getEntityStateFieldDefinitions($entity_type_id, $bundle_id)) {
      return reset($field_definitions);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityStateFieldDefinitions($entity_type_id, $bundle_id) {
    return array_filter($this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle_id), function (FieldDefinitionInterface $field_definition) {
      return $field_definition->getType() == 'state';
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableTransitionsLabels(FieldableEntityInterface $entity, AccountInterface $account = NULL) {
    return array_map(function (WorkflowTransition $transition) {
      return (string) $transition->getLabel();
    }, $this->getAvailableTransitions($entity, $account));
  }

  /**
   * {@inheritdoc}
   */
  public function hasEntityStateField($entity_type_id, $bundle_id) {
    return (bool) $this->getEntityStateFieldDefinitions($entity_type_id, $bundle_id);
  }

  /**
   * {@inheritdoc}
   */
  public function isWorkflowStatePublished($state_id, WorkflowInterface $workflow) {
    // We rely on being able to inspect the plugin definition. Throw an error if
    // this is not the case.
    if (!$workflow instanceof PluginInspectionInterface) {
      $label = $workflow->getLabel();
      throw new \InvalidArgumentException("The '$label' workflow is not plugin based.");
    }

    // Retrieve the raw plugin definition, as all additional plugin settings
    // are stored there.
    $raw_workflow_definition = $workflow->getPluginDefinition();
    return !empty($raw_workflow_definition['states'][$state_id]['published']);
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkflow(EntityInterface $entity, $state_field_name = NULL) {
    if (empty($state_field_name)) {
      $state_field_item = $this->getEntityStateField($entity);
      if (empty($state_field_item)) {
        return;
      }
      $state_field_name = $state_field_item->getName();
    }

    return $entity->get($state_field_name)->first()->getWorkflow();
  }

  /**
   * {@inheritdoc}
   */
  public function findTransitionOnUpdate(EntityInterface $entity, $state_field_name = NULL) {
    if (empty($state_field_name)) {
      $state_field_item = $this->getEntityStateField($entity);
      if (empty($state_field_item)) {
        return NULL;
      }
      $state_field_name = $state_field_item->getName();
    }

    // If there is no original version, then it is not an update.
    if (empty($entity->original)) {
      return NULL;
    }

    /** @var \Drupal\state_machine\Plugin\Workflow\WorkflowInterface $workflow */
    $workflow = $entity->get($state_field_name)->first()->getWorkflow();
    $original_state = $entity->original->get($state_field_name)->first()->value;
    $target_state = $entity->get($state_field_name)->first()->value;
    if ($original_state !== $target_state) {
      return NULL;
    }

    $transition = $workflow->findTransition($original_state, $target_state);
    return $transition;
  }

  /**
   * {@inheritdoc}
   */
  public function userHasOwnAnyRoles(EntityInterface $entity, AccountInterface $account, array $roles) {
    $own = $entity->getOwnerId() === $account->id();
    if (isset($roles['any']) && $this->userHasRoles($entity, $account, $roles['any'])) {
      return TRUE;
    }
    if ($own && isset($roles['own']) && $this->userHasRoles($entity, $account, $roles['own'])) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function userHasRoles(EntityInterface $entity, AccountInterface $account, array $roles) {
    $parent = $this->getEntityParent($entity);
    if (empty($parent)) {
      return FALSE;
    }

    $membership = $this->membershipManager->getMembership($parent, $account);

    // First check the 'any' permissions.
    if (isset($roles['roles'])) {
      if (array_intersect($account->getRoles(), $roles['roles'])) {
        return TRUE;
      }
    }
    if (isset($roles['og_roles']) && !empty($membership)) {
      if (array_intersect($membership->getRolesIds(), $roles['og_roles'])) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Helper method to retrieve the parent of the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group content entity.
   *
   * @return \Drupal\rdf_entity\RdfInterface|null
   *   The rdf entity the entity belongs to, or NULL when no group is found.
   */
  protected function getEntityParent(EntityInterface $entity) {
    $groups = $this->membershipManager->getGroups($entity);
    if (empty($groups['rdf_entity'])) {
      return NULL;
    }

    return reset($groups['rdf_entity']);
  }

}
