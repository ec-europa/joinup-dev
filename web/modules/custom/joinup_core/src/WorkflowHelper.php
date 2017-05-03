<?php

namespace Drupal\joinup_core;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
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
   * Constructs a WorkflowHelper.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The service that contains the current user.
   * @param \Drupal\Core\Session\AccountSwitcherInterface $accountSwitcher
   *   The account switcher interface.
   */
  public function __construct(AccountProxyInterface $currentUser, AccountSwitcherInterface $accountSwitcher) {
    $this->accountSwitcher = $accountSwitcher;
    $this->currentUser = $currentUser;
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
  public function getAvailableTransitionsLabels(FieldableEntityInterface $entity, AccountInterface $account = NULL) {
    return array_map(function (WorkflowTransition $transition) {
      return (string) $transition->getLabel();
    }, $this->getAvailableTransitions($entity, $account));
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableTransitions(FieldableEntityInterface $entity, AccountInterface $account = NULL) {
    // Set the current user so that states available are retrieved for the
    // specific account.
    $account_switched = FALSE;
    if ($account !== NULL && $account->id() !== $this->currentUser->id()) {
      $this->accountSwitcher->switchTo($account);
      $account_switched = TRUE;
    }

    $field = $this->getEntityStateField($entity);

    $transitions = $field->getTransitions();

    if ($account_switched) {
      $this->accountSwitcher->switchBack();
    }

    return $transitions;
  }

  /**
   * {@inheritdoc}
   */
  public static function getEntityStateFieldDefinitions(FieldableEntityInterface $entity) {
    return array_filter($entity->getFieldDefinitions(), function (FieldDefinitionInterface $field_definition) {
      return $field_definition->getType() == 'state';
    });
  }

  /**
   * {@inheritdoc}
   */
  public static function getEntityStateFieldDefinition(FieldableEntityInterface $entity) {
    if ($field_definitions = static::getEntityStateFieldDefinitions($entity)) {
      return reset($field_definitions);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityStateField(FieldableEntityInterface $entity) {
    $field_definition = $this->getEntityStateFieldDefinition($entity);
    if ($field_definition === NULL) {
      throw new \Exception('No state fields were found in the entity.');
    }
    return $entity->{$field_definition->getName()}->first();
  }

  /**
   * {@inheritdoc}
   */
  public function hasEntityStateField(FieldableEntityInterface $entity) {
    return (bool) static::getEntityStateFieldDefinitions($entity);
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

}
